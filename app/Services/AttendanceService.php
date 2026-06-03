<?php

namespace App\Services;

use App\Models\{
    AttendanceRecord, AttendanceSession, BatchCourse,
    BatchStudent, Schedule, User
};
use App\Notifications\{
    AttendanceMarkedNotification,
    AttendanceBelowThresholdNotification,
    SessionOpenedNotification
};
use Illuminate\Support\Facades\DB;

final class AttendanceService
{
    private const ATTENDANCE_THRESHOLD = 75.0;

    public function openSession(int $scheduleId, User $teacher): AttendanceSession
    {
        $schedule = Schedule::with(['batchCourse', 'timeSlot', 'room'])->findOrFail($scheduleId);

        if ($schedule->batchCourse->teacher_id !== $teacher->id) {
            throw new \RuntimeException('You are not assigned to this class.');
        }

        if (! $schedule->isWithinClassWindow()) {
            throw new \RuntimeException('You can only open attendance during your scheduled class time.');
        }

        $today    = today()->toDateString();
        $existing = AttendanceSession::where('schedule_id', $scheduleId)
            ->where('session_date', $today)->first();

        if ($existing && $existing->isOpen())              return $existing;
        if ($existing && $existing->status === 'closed') {
            throw new \RuntimeException('Attendance session for today has already been closed.');
        }

        return DB::transaction(function () use ($scheduleId, $teacher, $today, $schedule) {
            $session = AttendanceSession::create([
                'schedule_id'    => $scheduleId,
                'teacher_id'     => $teacher->id,
                'session_date'   => $today,
                'opened_at'      => now(),
                'status'         => 'open',
                'total_students' => $schedule->batchCourse->batch
                                        ->batchStudents()->where('status', 'active')->count(),
            ]);

            $this->notifyStudentsSessionOpen($session);
            return $session;
        });
    }

    public function closeSession(int $sessionId, User $teacher): AttendanceSession
    {
        $session = AttendanceSession::findOrFail($sessionId);

        if ($session->teacher_id !== $teacher->id) {
            throw new \RuntimeException('You cannot close this session.');
        }
        if (! $session->isOpen()) {
            throw new \RuntimeException('Session is not open.');
        }

        return DB::transaction(function () use ($session) {
            $this->markAbsentees($session);
            $session->recalculateTotals();
            $session->update(['status' => 'closed', 'closed_at' => now()]);
            return $session->fresh();
        });
    }

    public function markAttendance(int $sessionId, User $student, array $payload): AttendanceRecord
    {
        $session = AttendanceSession::with([
            'schedule.batchCourse.batch',
            'schedule.room',
        ])->findOrFail($sessionId);

        if (! $session->isOpen()) {
            throw new \RuntimeException('This attendance session is not currently open.');
        }

        $batchId    = $session->schedule->batchCourse->batch_id;
        $isEnrolled = BatchStudent::where('batch_id', $batchId)
            ->where('user_id', $student->id)->where('status', 'active')->exists();

        if (! $isEnrolled) {
            throw new \RuntimeException('You are not enrolled in this course\'s batch.');
        }

        $existing = AttendanceRecord::where('attendance_session_id', $sessionId)
            ->where('student_id', $student->id)->first();

        if ($existing) {
            throw new \RuntimeException('You have already marked attendance for this session.');
        }

        $room         = $session->schedule->room;
        $wifiVerified = $this->verifyWifi($room->wifi_bssid, $payload['wifi_bssid'] ?? null);
        $gpsVerified  = $this->verifyGps(
            $room->latitude, $room->longitude, $room->gps_radius_meters,
            $payload['latitude'] ?? null, $payload['longitude'] ?? null
        );

        if (! $wifiVerified) {
            throw new \RuntimeException('WiFi BSSID does not match the classroom network.');
        }
        if (! $gpsVerified) {
            throw new \RuntimeException('GPS location is outside the classroom radius.');
        }

        return DB::transaction(function () use ($session, $student, $payload, $wifiVerified, $gpsVerified) {
            $record = AttendanceRecord::create([
                'attendance_session_id' => $session->id,
                'student_id'            => $student->id,
                'status'                => 'present',
                'wifi_bssid_captured'   => $payload['wifi_bssid'] ?? null,
                'latitude_captured'     => $payload['latitude'] ?? null,
                'longitude_captured'    => $payload['longitude'] ?? null,
                'wifi_verified'         => $wifiVerified,
                'gps_verified'          => $gpsVerified,
                'ip_address'            => request()->ip(),
                'marked_at'             => now(),
            ]);

            $session->increment('total_present');
            $student->notify(new AttendanceMarkedNotification($session, $record));
            $this->checkAttendanceThreshold($student, $session);

            return $record;
        });
    }

    public function studentCoursePercentage(User $student, int $batchCourseId): array
    {
        $sessions = AttendanceSession::whereHas('schedule', fn($q) =>
            $q->where('batch_course_id', $batchCourseId)
        )->where('status', 'closed')->get();

        $total   = $sessions->count();
        $present = AttendanceRecord::where('student_id', $student->id)
            ->whereIn('attendance_session_id', $sessions->pluck('id'))
            ->where('status', 'present')->count();

        return [
            'total_sessions'     => $total,
            'attended'           => $present,
            'absent'             => $total - $present,
            'percentage'         => $total > 0 ? round(($present / $total) * 100, 2) : 0.0,
            'is_below_threshold' => $total > 0
                ? ($present / $total * 100) < self::ATTENDANCE_THRESHOLD
                : false,
        ];
    }

    public function weakStudents(int $batchId, float $threshold = self::ATTENDANCE_THRESHOLD): array
    {
        $batchCourses = BatchCourse::where('batch_id', $batchId)->with('course')->get();
        $students     = BatchStudent::where('batch_id', $batchId)
            ->where('status', 'active')->with('student')->get();

        $result = [];
        foreach ($students as $bs) {
            $studentData = ['student' => $bs->student->only(['id','name','email']), 'courses' => []];
            $anyBelow    = false;

            foreach ($batchCourses as $bc) {
                $stats = $this->studentCoursePercentage($bs->student, $bc->id);
                if ($stats['percentage'] < $threshold) {
                    $studentData['courses'][] = array_merge($stats, ['course' => $bc->course->name]);
                    $anyBelow = true;
                }
            }

            if ($anyBelow) $result[] = $studentData;
        }

        return $result;
    }

    private function verifyWifi(?string $registered, ?string $captured): bool
    {
        if (! $registered) return true;
        if (! $captured)   return false;
        return strtolower(trim($registered)) === strtolower(trim($captured));
    }

    private function verifyGps(?float $rLat, ?float $rLng, int $radius, ?float $sLat, ?float $sLng): bool
    {
        if (! $rLat || ! $rLng) return true;
        if (! $sLat || ! $sLng) return false;
        return $this->haversineDistance($rLat, $rLng, $sLat, $sLng) <= $radius;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 6371000;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);
        $a  = sin($Δφ/2)**2 + cos($φ1)*cos($φ2)*sin($Δλ/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }

    private function markAbsentees(AttendanceSession $session): void
    {
        $batchId    = $session->schedule->batchCourse->batch_id;
        $studentIds = BatchStudent::where('batch_id', $batchId)
            ->where('status', 'active')->pluck('user_id');
        $markedIds  = AttendanceRecord::where('attendance_session_id', $session->id)->pluck('student_id');
        $absentIds  = $studentIds->diff($markedIds);

        $inserts = $absentIds->map(fn($sid) => [
            'attendance_session_id' => $session->id,
            'student_id'            => $sid,
            'status'                => 'absent',
            'wifi_verified'         => false,
            'gps_verified'          => false,
            'created_at'            => now(),
            'updated_at'            => now(),
        ])->values()->toArray();

        if ($inserts) AttendanceRecord::insert($inserts);
    }

    private function checkAttendanceThreshold(User $student, AttendanceSession $session): void
    {
        $stats = $this->studentCoursePercentage($student, $session->schedule->batchCourse->id);
        if ($stats['is_below_threshold']) {
            $student->notify(new AttendanceBelowThresholdNotification($stats));
        }
    }

    private function notifyStudentsSessionOpen(AttendanceSession $session): void
    {
        $batchId    = $session->schedule->batchCourse->batch_id;
        $studentIds = BatchStudent::where('batch_id', $batchId)
            ->where('status', 'active')->pluck('user_id');

        User::whereIn('id', $studentIds)->get()
            ->each(fn($s) => $s->notify(new SessionOpenedNotification($session)));
    }
}
