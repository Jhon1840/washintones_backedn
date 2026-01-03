<?php

namespace App\Http\Middleware;

use App\Services\AuthTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    /**
     * Permite continuar solo si el token pertenece a un administrador activo.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        if (! $usuario) {
            return response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401);
        }

        if (! $usuario->es_admin) {
            return response()->json([
                'message' => 'Solo los administradores pueden acceder a esta ruta.',
            ], 403);
        }

        $request->setUserResolver(fn () => $usuario);

        return $next($request);
    }
}
