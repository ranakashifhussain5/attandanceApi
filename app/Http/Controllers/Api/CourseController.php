<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Course, Program};
use Illuminate\Http\{JsonResponse, Request};

class CourseController extends Controller
{
    public function index(Program $program): JsonResponse
    {
        return response()->json($program->courses()->withCount('batchCourses')->paginate(20));
    }

    public function store(Request $request, Program $program): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'required|string|max:20|unique:courses',
            'credit_hours' => 'required|integer|min:1|max:4',
            'semester'     => 'required|integer|min:1',
            'description'  => 'nullable|string',
        ]);
        return response()->json($program->courses()->create($data), 201);
    }

    public function update(Request $request, Program $program, Course $course): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:100',
            'credit_hours' => 'sometimes|integer|min:1|max:4',
            'semester'     => 'sometimes|integer|min:1',
            'is_active'    => 'sometimes|boolean',
        ]);
        $course->update($data);
        return response()->json($course);
    }

    public function destroy(Program $program, Course $course): JsonResponse
    {
        $course->delete();
        return response()->json(['message' => 'Course deleted.']);
    }
}
