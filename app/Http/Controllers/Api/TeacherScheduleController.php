<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceSession, Schedule};
use App\Services\AttendanceService;
use Illuminate\Http\{JsonResponse, Request};

class TeacherScheduleController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function mySchedule(Request $request): JsonResponse
    {
        $schedules = Schedule::with(['batchCourse.course', 'batchCourse.batch', 'room', 'timeSlot'])
            ->whereHas('batchCourse', fn($q) => $q->where('teacher_id', $request->user()->id))
            ->where('is_active', true)
            ->orderBy('day_of_week')->orderBy('time_slot_id')
            ->get()->groupBy('day_of_week');

        return response()->json($schedules);
    }

    public function openSession(Request $request): JsonResponse
    {
        $data = $request->validate(['schedule_id' => 'required|exists:schedules,id']);

        try {
            $session = $this->attendanceService->openSession($data['schedule_id'], $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Attendance session opened.',
            'session' => $session->load('schedule.batchCourse.course'),
        ], 201);
    }

    public function closeSession(Request $request, AttendanceSession $session): JsonResponse
    {
        try {
            $session = $this->attendanceService->closeSession($session->id, $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Session closed.', 'session' => $session->load('records')]);
    }

    public function sessionRecords(Request $request, AttendanceSession $session): JsonResponse
    {
        if ($session->teacher_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'session'        => $session,
            'total_present'  => $session->total_present,
            'total_students' => $session->total_students,
            'records'        => $session->records()->with('student:id,name,email')->get(),
        ]);
    }
}
