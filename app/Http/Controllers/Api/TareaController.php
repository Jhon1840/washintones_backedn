<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de tareas pendiente de implementación.',
            'filtros' => $request->only(['hoy', 'vencidas']),
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear tarea pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar tarea pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }
}
