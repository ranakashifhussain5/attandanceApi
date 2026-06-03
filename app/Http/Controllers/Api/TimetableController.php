<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Batch, Schedule, TimetableGenerationLog};
use App\Services\TimetableGeneratorService;
use Illuminate\Http\{JsonResponse, Request};

class TimetableController extends Controller
{
    public function __construct(private TimetableGeneratorService $generator) {}

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate(['semester' => 'required|integer|min:1']);

        $log = $this->generator->generate(
            $request->user()->department_id,
            $data['semester'],
            $request->user()->id
        );

        return response()->json([
            'message' => $log->status === 'success'
                ? "Timetable generated: {$log->schedules_created} classes scheduled."
                : 'Generation failed.',
            'log'     => $log,
        ], $log->status === 'failed' ? 500 : 200);
    }

    public function batchTimetable(Batch $batch): JsonResponse
    {
        $schedules = Schedule::with([
            'batchCourse.course', 'batchCourse.teacher', 'room', 'timeSlot',
        ])
        ->whereHas('batchCourse', fn($q) => $q->where('batch_id', $batch->id))
        ->where('is_active', true)
        ->orderBy('day_of_week')->orderBy('time_slot_id')
        ->get()->groupBy('day_of_week');

        return response()->json($schedules);
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = TimetableGenerationLog::where('department_id', $request->user()->department_id)
            ->latest()->paginate(10);
        return response()->json($logs);
    }
}
