<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceRecord, AttendanceSession, Batch, BatchCourse, BatchStudent};
use App\Services\AttendanceService;
use Illuminate\Http\{JsonResponse, Request};

class AnalyticsController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function attendanceTrends(Request $request): JsonResponse
    {
        $deptId = $request->user()->department_id;

        $trends = BatchCourse::with(['course', 'batch'])
            ->whereHas('batch.program', fn($q) => $q->where('department_id', $deptId))
            ->get()
            ->map(function (BatchCourse $bc) {
                $sessions = AttendanceSession::whereHas('schedule', fn($q) =>
                    $q->where('batch_course_id', $bc->id)
                )->where('status', 'closed');

                $total    = $sessions->count();
                $enrolled = BatchStudent::where('batch_id', $bc->batch_id)->where('status', 'active')->count();
                $present  = AttendanceRecord::whereIn('attendance_session_id', $sessions->pluck('id'))
                    ->where('status', 'present')->count();
                $possible = $total * $enrolled;

                return [
                    'batch_course_id' => $bc->id,
                    'course'          => $bc->course->name,
                    'batch'           => $bc->batch->name,
                    'total_sessions'  => $total,
                    'avg_percentage'  => $possible > 0 ? round($present / $possible * 100, 2) : 0,
                ];
            });

        return response()->json($trends);
    }

    public function weakStudents(Batch $batch): JsonResponse
    {
        $weak = $this->attendanceService->weakStudents($batch->id);
        return response()->json(['count' => count($weak), 'students' => $weak]);
    }

    public function batchPerformance(Batch $batch): JsonResponse
    {
        $courses = BatchCourse::where('batch_id', $batch->id)->with('course')->get();

        $performance = $courses->map(function (BatchCourse $bc) use ($batch) {
            $sessions = AttendanceSession::whereHas('schedule', fn($q) =>
                $q->where('batch_course_id', $bc->id)
            )->where('status', 'closed');

            $total    = $sessions->count();
            $enrolled = BatchStudent::where('batch_id', $batch->id)->where('status', 'active')->count();
            $present  = AttendanceRecord::whereIn('attendance_session_id', $sessions->pluck('id'))
                ->where('status', 'present')->count();
            $possible = $total * $enrolled;

            return [
                'course'         => $bc->course->name,
                'sessions'       => $total,
                'avg_attendance' => $possible > 0 ? round($present / $possible * 100, 2) : 0,
                'enrolled'       => $enrolled,
            ];
        });

        return response()->json([
            'batch'       => $batch->only(['id', 'name', 'semester']),
            'performance' => $performance,
        ]);
    }
}
