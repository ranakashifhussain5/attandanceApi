<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{TeacherAvailability, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Hash;

class HodTeacherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teachers = User::teachers()
            ->where('department_id', $request->user()->department_id)
            ->with('availability')
            ->withCount('assignedBatchCourses')
            ->paginate(20);
        return response()->json($teachers);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
        ]);

        $teacher = User::create([
            ...$data,
            'role'          => 'teacher',
            'department_id' => $request->user()->department_id,
            'password'      => Hash::make('Welcome@123'),
        ]);

        return response()->json($teacher, 201);
    }

    public function setAvailability(Request $request, User $teacher): JsonResponse
    {
        $data = $request->validate([
            'availability'                  => 'required|array',
            'availability.*.day_of_week'    => 'required|integer|min:1|max:5',
            'availability.*.available_from' => 'required|date_format:H:i',
            'availability.*.available_to'   => 'required|date_format:H:i',
        ]);

        TeacherAvailability::where('teacher_id', $teacher->id)->delete();

        $inserts = collect($data['availability'])->map(fn($a) => [
            'teacher_id'     => $teacher->id,
            'day_of_week'    => $a['day_of_week'],
            'available_from' => $a['available_from'],
            'available_to'   => $a['available_to'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ])->toArray();

        TeacherAvailability::insert($inserts);

        return response()->json(['message' => 'Availability updated.', 'slots' => count($inserts)]);
    }
}
