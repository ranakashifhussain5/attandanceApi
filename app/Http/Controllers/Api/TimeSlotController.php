<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use Illuminate\Http\{JsonResponse, Request};

class TimeSlotController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(TimeSlot::where('is_active', true)->orderBy('start_time')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'            => 'required|string|max:40',
            'start_time'       => 'required|date_format:H:i',
            'end_time'         => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|min:30|max:240',
        ]);
        return response()->json(TimeSlot::create($data), 201);
    }

    public function update(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        $timeSlot->update($request->validate(['is_active' => 'required|boolean']));
        return response()->json($timeSlot);
    }

    public function destroy(TimeSlot $timeSlot): JsonResponse
    {
        $timeSlot->delete();
        return response()->json(['message' => 'Time slot removed.']);
    }
}
