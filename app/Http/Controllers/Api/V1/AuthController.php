<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required_without:phone', 'nullable', 'string', 'email'],
            'phone' => ['required_without:email', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = $credentials['email'] ?? $credentials['phone'] ?? null;

        if ($identifier === null) {
            return response()->json([
                'message' => 'Either email or phone is required',
            ], 422);
        }

        $query = User::query();

        if (!empty($credentials['email'])) {
            $query->where('email', $credentials['email']);
        } else {
            $query->where('phone', $credentials['phone']);
        }

        /** @var \App\Models\User|null $user */
        $user = $query->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->is_banned) {
            return response()->json([
                'message' => 'User is banned',
            ], 403);
        }

        $token = $user->createToken('api', ['*'], now()->addMonth())->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
            'message' => 'User logged in successfully',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
            'message' => 'User data retrieved successfully',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
