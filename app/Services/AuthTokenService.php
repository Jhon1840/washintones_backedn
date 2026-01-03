<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthTokenService
{
    private const TOKEN_TTL_MINUTES = 60;

    public function issueToken(int $userId): string
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

    public function resolveUserFromRequest(Request $request): ?Usuario
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

    public function expiresInSeconds(): int
    {
        return self::TOKEN_TTL_MINUTES * 60;
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
