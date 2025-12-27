<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Historial unificado pendiente de implementación.',
            'filtros' => $request->only([
                'entidad',
                'cliente_id',
                'inmueble_id',
                'usuario_id',
                'fecha_inicio',
                'fecha_fin',
            ]),
            'data' => [],
        ]);
    }
}
