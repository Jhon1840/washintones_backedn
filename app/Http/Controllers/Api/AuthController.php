<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Suscripcion;
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

        $subscription = $this->resolveSubscriptionStatus($usuario);
        if (! $usuario->es_admin && ! $this->hasActiveSubscription($subscription)) {
            return response()->json([
                'message' => $subscription['mensaje'] ?? 'No tiene una suscripcion activa.',
                'suscripcion_vencida' => (bool) $subscription['vencida'],
                'suscripcion_estado' => $subscription['estado'],
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

        $subscription = $this->resolveSubscriptionStatus($usuario);
        if (! $usuario->es_admin && ! $this->hasActiveSubscription($subscription)) {
            return response()->json([
                'message' => $subscription['mensaje'] ?? 'No tiene una suscripcion activa.',
                'suscripcion_vencida' => (bool) $subscription['vencida'],
                'suscripcion_estado' => $subscription['estado'],
            ], 403);
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
        $subscription = $this->resolveSubscriptionStatus($usuario);

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
                'suscripcion_id' => $subscription['id'],
                'suscripcion_estado' => $subscription['estado'],
                'suscripcion_fecha_fin' => $subscription['fecha_fin'],
                'suscripcion_vencida' => $subscription['vencida'],
                'suscripcion_mensaje' => $subscription['mensaje'],
            ],
        ];
    }

    private function resolveSubscriptionStatus(Usuario $usuario): array
    {
        $suscripcion = Suscripcion::query()
            ->where('usuario_id', $usuario->id)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->first();

        if (! $suscripcion) {
            return [
                'id' => null,
                'estado' => null,
                'fecha_fin' => null,
                'vencida' => false,
                'mensaje' => 'No tiene una suscripcion activa.',
            ];
        }

        $hoy = now()->toDateString();
        $vencida = $suscripcion->fecha_fin !== null
            && $suscripcion->fecha_fin->toDateString() < $hoy;

        if ($vencida && strtolower((string) $suscripcion->estado) !== 'inactiva') {
            $suscripcion->estado = 'inactiva';
            $suscripcion->save();
        }

        return [
            'id' => $suscripcion->id,
            'estado' => $suscripcion->estado,
            'fecha_fin' => $suscripcion->fecha_fin?->toDateString(),
            'vencida' => $vencida,
            'mensaje' => $vencida ? 'La suscripcion esta vencida.' : null,
        ];
    }

    private function hasActiveSubscription(array $subscription): bool
    {
        return strtolower((string) ($subscription['estado'] ?? '')) === 'activa';
    }
}
