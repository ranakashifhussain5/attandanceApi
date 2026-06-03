<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceSession, BatchCourse, Schedule};
use App\Services\AttendanceService;
use Illuminate\Http\{JsonResponse, Request};

class StudentAttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function mark(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
            'wifi_bssid' => 'nullable|string',
            'latitude'   => 'nullable|numeric|between:-90,90',
            'longitude'  => 'nullable|numeric|between:-180,180',
        ]);

        try {
            $record = $this->attendanceService->markAttendance(
                $data['session_id'], $request->user(), $data
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Attendance marked successfully.', 'record' => $record]);
    }

    public function history(Request $request): JsonResponse
    {
        $records = $request->user()->attendanceRecords()
            ->with(['session.schedule.batchCourse.course', 'session.schedule.timeSlot'])
            ->latest('marked_at')->paginate(20);
        return response()->json($records);
    }

    public function summary(Request $request): JsonResponse
    {
        $student      = $request->user();
        $batchCourses = BatchCourse::whereHas('batch.batchStudents', fn($q) =>
            $q->where('user_id', $student->id)->where('status', 'active')
        )->with(['course', 'batch'])->get();

        $summary = $batchCourses->map(function (BatchCourse $bc) use ($student) {
            return array_merge(
                ['course' => $bc->course->name, 'batch' => $bc->batch->name],
                $this->attendanceService->studentCoursePercentage($student, $bc->id)
            );
        });

        return response()->json($summary);
    }

    public function mySchedule(Request $request): JsonResponse
    {
        $student  = $request->user();
        $batchIds = $student->batches()->pluck('batches.id');

        $schedules = Schedule::with(['batchCourse.course', 'batchCourse.teacher', 'room', 'timeSlot'])
            ->whereHas('batchCourse', fn($q) => $q->whereIn('batch_id', $batchIds))
            ->where('is_active', true)
            ->orderBy('day_of_week')->orderBy('time_slot_id')
            ->get()->groupBy('day_of_week');

        return response()->json($schedules);
    }

    public function activeSessions(Request $request): JsonResponse
    {
        $batchIds = $request->user()->batches()->pluck('batches.id');

        $sessions = AttendanceSession::with([
            'schedule.batchCourse.course', 'schedule.room', 'schedule.timeSlot',
        ])
        ->where('status', 'open')
        ->whereHas('schedule.batchCourse', fn($q) => $q->whereIn('batch_id', $batchIds))
        ->get();

        return response()->json($sessions);
    }
}
