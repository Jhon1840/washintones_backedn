<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InmuebleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de inmuebles pendiente de implementación.',
            'filtros' => $request->only(['cliente_id', 'zona', 'estado', 'tipo']),
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear inmueble pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de inmueble pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar inmueble pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Eliminar inmueble pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function storeFoto(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Cargar foto de inmueble pendiente de implementación.',
            'inmueble_id' => $id,
            'data' => $request->all(),
        ], 201);
    }

    public function destroyFoto(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Eliminar foto de inmueble pendiente de implementación.',
            'foto_id' => $id,
        ]);
    }

    public function storeDocumento(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Cargar documento de inmueble pendiente de implementación.',
            'inmueble_id' => $id,
            'data' => $request->all(),
        ], 201);
    }

    public function destroyDocumento(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Eliminar documento de inmueble pendiente de implementación.',
            'documento_id' => $id,
        ]);
    }
}
