<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Batch, BatchCourse, BatchStudent, User};
use Illuminate\Http\{JsonResponse, Request};

class BatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $batches = Batch::whereHas('program', fn($q) =>
                $q->where('department_id', $request->user()->department_id)
            )
            ->when($request->program_id, fn($q) => $q->where('program_id', $request->program_id))
            ->when($request->start_year, fn($q) => $q->where('start_year', $request->start_year))
            ->with('program')
            ->withCount(['batchStudents', 'batchCourses'])
            ->paginate(15);

        return response()->json($batches);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_id'   => 'required|exists:programs,id',
            'name'         => 'required|string|max:60',
            'semester'     => 'required|integer|min:1',
            'start_year'   => 'required|integer',
            'end_year'     => 'required|integer|gt:start_year',
            'max_students' => 'required|integer|min:5|max:200',
        ]);
        return response()->json(Batch::create($data)->load('program'), 201);
    }

    public function show(Batch $batch): JsonResponse
    {
        return response()->json(
            $batch->load(['program', 'batchStudents.student', 'batchCourses.course'])
        );
    }

    public function update(Request $request, Batch $batch): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:60',
            'semester'     => 'sometimes|integer|min:1',
            'max_students' => 'sometimes|integer|min:5|max:200',
            'is_active'    => 'sometimes|boolean',
        ]);
        $batch->update($data);
        return response()->json($batch);
    }

    public function addStudents(Request $request, Batch $batch): JsonResponse
    {
        $data      = $request->validate([
            'student_ids'   => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);
        $enrolled  = 0;
        $alreadyIn = 0;

        foreach ($data['student_ids'] as $sid) {
            $student = User::find($sid);
            if (!$student || !$student->isStudent()) continue;

            $exists = BatchStudent::where('batch_id', $batch->id)
                                  ->where('user_id', $sid)->exists();
            if ($exists) { $alreadyIn++; continue; }

            BatchStudent::create([
                'batch_id'    => $batch->id,
                'user_id'     => $sid,
                'enrolled_at' => today()->toDateString(),
                'status'      => 'active',
            ]);
            $enrolled++;
        }

        return response()->json(['enrolled' => $enrolled, 'already_in' => $alreadyIn]);
    }

    public function assignCourse(Request $request, Batch $batch): JsonResponse
    {
        $data    = $request->validate([
            'course_id'  => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:users,id',
        ]);
        $teacher = User::findOrFail($data['teacher_id']);

        if (!$teacher->isTeacher() || $teacher->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'Invalid teacher for this department.'], 422);
        }

        $bc = BatchCourse::updateOrCreate(
            ['batch_id' => $batch->id, 'course_id' => $data['course_id']],
            ['teacher_id' => $data['teacher_id'], 'is_active' => true]
        );

        return response()->json($bc->load(['course', 'teacher']), 201);
    }

    public function sections(Batch $batch): JsonResponse
    {
        return response()->json([
            ['id' => 1, 'name' => 'Morning'],
            ['id' => 2, 'name' => 'Evening'],
        ]);
    }

    public function semesterCourses(Request $request, Batch $batch): JsonResponse
    {
        $request->validate([
            'semester'   => 'required|integer|min:1',
            'section_id' => 'nullable|integer',
        ]);

        $courses = BatchCourse::where('batch_id', $batch->id)
            ->where('is_active', true)
            ->whereHas('course', fn($q) => $q->where('semester', $request->semester))
            ->with([
                'course:id,name,code,credit_hours,semester',
                'teacher:id,name,email',
            ])
            ->get()
            ->map(fn($bc) => [
                'course_id'    => $bc->course_id,
                'code'         => $bc->course->code,
                'name'         => $bc->course->name,
                'credit_hours' => $bc->course->credit_hours,
                'semester'     => $bc->course->semester,
                'teacher'      => $bc->teacher ? [
                    'id'    => $bc->teacher->id,
                    'name'  => $bc->teacher->name,
                    'email' => $bc->teacher->email,
                ] : null,
            ]);

        return response()->json($courses);
    }
}