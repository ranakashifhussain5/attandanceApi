<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Department, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|unique:users',
            'password'        => ['required', Password::min(8)->mixedCase()->numbers()],
            'role'            => 'required|in:hod,teacher,student',
            'department_name' => 'required|string|max:100',
            'phone'           => 'nullable|string|max:20',
        ]);

        // Department name se dhundo, agar nahi hai toh khud bana do
        $department = Department::firstOrCreate(
            ['name' => trim($data['department_name'])],
            [
                'name' => trim($data['department_name']),
                'code' => strtoupper(substr(preg_replace('/[^A-Z]/', '', strtoupper($data['department_name'])), 0, 5)),
            ]
        );

        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'role'          => $data['role'],
            'department_id' => $department->id,
            'phone'         => $data['phone'] ?? null,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token'   => $token,
            'user'    => $user->load('department'),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->load('department'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('department'));
    }
}