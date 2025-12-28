<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const TOKEN_TTL_MINUTES = 60;

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
        $usuario = $this->resolveUserFromRequest($request);

        if (! $usuario) {
            return response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401);
        }

        return response()->json($this->tokenResponse($usuario));
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $this->resolveUserFromRequest($request);

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
            'activo' => (bool) $usuario->activo,
        ]);
    }

    private function tokenResponse(Usuario $usuario): array
    {
        $token = $this->issueToken($usuario->getKey());

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => self::TOKEN_TTL_MINUTES * 60,
            'user' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
            ],
        ];
    }

    private function issueToken(int $userId): string
    {
        $payload = [
            'sub' => $userId,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(self::TOKEN_TTL_MINUTES)->timestamp,
            'jti' => (string) Str::uuid(),
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $encodedPayload, config('app.key'));

        return "{$encodedPayload}.{$signature}";
    }

    private function resolveUserFromRequest(Request $request): ?Usuario
    {
        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        $payload = $this->decodeToken($token);

        if (! $payload) {
            return null;
        }

        return Usuario::find($payload['sub'] ?? null);
    }

    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $encodedPayload, config('app.key'));

        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (! $payload || ($payload['exp'] ?? 0) < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $converted = strtr($value, '-_', '+/');
        $remainder = strlen($converted) % 4;

        if ($remainder) {
            $converted .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode($converted) ?: '';
    }
}
