<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de visitas pendiente de implementación.',
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear visita pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de visita pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar visita pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    public function acciones(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Acciones de visita pendientes de implementación.',
            'visita_id' => $id,
            'data' => [],
        ]);
    }

    public function storeAccion(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Crear acción de visita pendiente de implementación.',
            'visita_id' => $id,
            'data' => $request->all(),
        ], 201);
    }

    public function updateAccion(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar acción de visita pendiente de implementación.',
            'accion_id' => $id,
            'data' => $request->all(),
        ]);
    }
}
