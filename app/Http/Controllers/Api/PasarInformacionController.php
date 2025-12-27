<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasarInformacionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de pasar información pendiente de implementación.',
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear pasar información pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de pasar información pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar pasar información pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    public function historial(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Historial de pasar información pendiente de implementación.',
            'pasar_informacion_id' => $id,
            'data' => [],
        ]);
    }
}
