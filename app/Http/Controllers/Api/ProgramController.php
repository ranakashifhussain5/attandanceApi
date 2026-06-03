<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Program, User};
use Illuminate\Http\{JsonResponse, Request};

class ProgramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $programs = Program::where('department_id', $request->user()->department_id)
            ->withCount(['courses', 'batches'])->paginate(15);
        return response()->json($programs);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'required|string|max:20|unique:programs',
            'duration_years' => 'required|integer|min:1|max:6',
            'description'    => 'nullable|string',
        ]);

        $program = Program::create([...$data, 'department_id' => $request->user()->department_id]);
        return response()->json($program, 201);
    }

    public function show(Program $program): JsonResponse
    {
        return response()->json($program->load(['courses', 'batches']));
    }

    public function update(Request $request, Program $program): JsonResponse
    {
        $this->authorizeHodProgram($program, $request->user());
        $data = $request->validate([
            'name'           => 'sometimes|string|max:100',
            'duration_years' => 'sometimes|integer|min:1|max:6',
            'description'    => 'nullable|string',
            'is_active'      => 'sometimes|boolean',
        ]);
        $program->update($data);
        return response()->json($program);
    }

    public function destroy(Request $request, Program $program): JsonResponse
    {
        $this->authorizeHodProgram($program, $request->user());
        $program->delete();
        return response()->json(['message' => 'Program deleted.']);
    }

    private function authorizeHodProgram(Program $program, User $hod): void
    {
        if ($program->department_id !== $hod->department_id) abort(403, 'Unauthorized.');
    }
}
