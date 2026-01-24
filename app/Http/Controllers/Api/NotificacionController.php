<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = max((int) $request->query('limit', 50), 1);

        $items = Notificacion::where('usuario_id', $usuario->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Notificaciones recuperadas.',
            'data' => $items,
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $notificacion = Notificacion::where('usuario_id', $usuario->id)->find($id);

        if (! $notificacion) {
            return response()->json([
                'message' => 'Notificación no encontrada.',
            ], 404);
        }

        $notificacion->leida_at = now();
        $notificacion->save();

        return response()->json([
            'message' => 'Notificación marcada como leída.',
            'data' => $notificacion,
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
