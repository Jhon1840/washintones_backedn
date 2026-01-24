<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $data = $request->validate([
            'token' => ['required', 'string'],
            'plataforma' => ['nullable', 'string', 'max:50'],
        ]);

        $record = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'usuario_id' => $usuario->id,
                'plataforma' => $data['plataforma'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Token registrado.',
            'data' => $record,
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        DeviceToken::where('usuario_id', $usuario->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json([
            'message' => 'Token eliminado.',
        ]);
    }

    private function requireUsuario(Request $request): Usuario
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        if (! $usuario) {
            throw new HttpResponseException(response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401));
        }

        return $usuario;
    }
}
