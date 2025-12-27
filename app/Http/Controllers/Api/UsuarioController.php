<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de usuarios pendientes de implementación.',
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear usuario pendiente de implementación.',
            'data' => $this->payload($request),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de usuario pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar usuario pendiente de implementación.',
            'data' => array_merge(['id' => $id], $this->payload($request)),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Eliminar usuario pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    private function payload(Request $request): array
    {
        return $request->only([
            'nombre',
            'email',
            'telefono',
            'activo',
            'rol_id',
        ]);
    }
}
