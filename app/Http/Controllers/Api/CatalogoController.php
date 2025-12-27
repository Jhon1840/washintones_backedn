<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    public function tiposInmueble(): JsonResponse
    {
        return response()->json($this->catalogo('tipos_inmueble'));
    }

    public function zonas(): JsonResponse
    {
        return response()->json($this->catalogo('zonas'));
    }

    public function operaciones(): JsonResponse
    {
        return response()->json($this->catalogo('operaciones'));
    }

    public function estadosAmc(): JsonResponse
    {
        return response()->json($this->catalogo('estados_amc'));
    }

    public function acciones(): JsonResponse
    {
        return response()->json($this->catalogo('acciones'));
    }

    public function monedas(): JsonResponse
    {
        return response()->json($this->catalogo('monedas'));
    }

    public function asesores(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Catálogo de asesores pendiente de implementación.',
            'filtros' => $request->only(['oficina_id', 'usuario_id']),
            'data' => [],
        ]);
    }

    private function catalogo(string $nombre): array
    {
        return [
            'message' => "Catálogo {$nombre} pendiente de implementación.",
            'data' => [],
        ];
    }
}
