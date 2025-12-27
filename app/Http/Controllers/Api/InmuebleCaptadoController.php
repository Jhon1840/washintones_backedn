<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InmuebleCaptadoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de inmuebles captados pendiente de implementación.',
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear inmueble captado pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de inmueble captado pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar inmueble captado pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    public function historial(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Historial de inmueble captado pendiente de implementación.',
            'inmueble_captado_id' => $id,
            'data' => [],
        ]);
    }
}
