<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Login endpoint pendiente de implementación.',
            'data' => $request->only(['email', 'password']),
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json([
            'message' => 'Logout endpoint pendiente de implementación.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'message' => 'Refresh endpoint pendiente de implementación.',
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'message' => 'Perfil actual pendiente de implementación.',
        ]);
    }
}
