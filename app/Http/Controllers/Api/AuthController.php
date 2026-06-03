<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users',
            'password'      => ['required', Password::min(8)->mixedCase()->numbers()],
            'role'          => 'required|in:hod,teacher,student',
            'department_id' => 'required|exists:departments,id',
            'phone'         => 'nullable|string|max:20',
        ]);

        $user  = User::create([...$data, 'password' => Hash::make($data['password'])]);
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

        return response()->json(['token' => $token, 'user' => $user->load('department')]);
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
