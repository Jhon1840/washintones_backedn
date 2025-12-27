<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColocacionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de colocaciones pendiente de implementación.',
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear colocación pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de colocación pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar colocación pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    public function historial(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Historial de colocación pendiente de implementación.',
            'colocacion_id' => $id,
            'data' => [],
        ]);
    }
}
