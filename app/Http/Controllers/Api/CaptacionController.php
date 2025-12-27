<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaptacionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de captaciones pendiente de implementación.',
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear captación pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de captación pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar captación pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    public function historial(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Historial de captación pendiente de implementación.',
            'captacion_id' => $id,
            'data' => [],
        ]);
    }

    public function proximasAcciones(): JsonResponse
    {
        return response()->json([
            'message' => 'Próximas acciones de captación pendientes de implementación.',
            'data' => [],
        ]);
    }
}
