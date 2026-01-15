<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('email', $credentials['email'])->first();

        if (! $usuario || ! Hash::check($credentials['password'], $usuario->password)) {
            return response()->json([
                'message' => 'Credenciales invalidas.',
            ], 422);
        }

        if (! $usuario->activo) {
            return response()->json([
                'message' => 'El usuario se encuentra inactivo.',
            ], 403);
        }

        return response()->json($this->tokenResponse($usuario));
    }

    public function logout(): JsonResponse
    {
        return response()->json([
            'message' => 'Sesion finalizada (tokens se invalidan con el cliente).',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        if (! $usuario) {
            return response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401);
        }

        return response()->json($this->tokenResponse($usuario));
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        if (! $usuario) {
            return response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401);
        }

        return response()->json([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
            'foto_url' => $usuario->foto_url,
            'activo' => (bool) $usuario->activo,
            'es_admin' => (bool) $usuario->es_admin,
        ]);
    }

    public function updatePhotoUrl(Request $request): JsonResponse
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        if (! $usuario) {
            return response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401);
        }

        $data = $request->validate([
            'foto_url' => ['required', 'string', 'max:2048'],
        ]);

        $usuario->foto_url = $data['foto_url'];
        $usuario->save();

        return response()->json([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
            'foto_url' => $usuario->foto_url,
            'activo' => (bool) $usuario->activo,
            'es_admin' => (bool) $usuario->es_admin,
        ]);
    }

    private function tokenResponse(Usuario $usuario): array
    {
        $token = $this->tokens->issueToken($usuario->getKey());

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->tokens->expiresInSeconds(),
            'user' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'foto_url' => $usuario->foto_url,
                'es_admin' => (bool) $usuario->es_admin,
            ],
        ];
    }
}
