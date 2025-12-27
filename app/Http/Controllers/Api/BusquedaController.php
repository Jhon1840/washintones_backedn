<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusquedaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Listado de búsquedas pendiente de implementación.',
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Crear búsqueda pendiente de implementación.',
            'data' => $request->all(),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Detalle de búsqueda pendiente de implementación.',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Actualizar búsqueda pendiente de implementación.',
            'data' => array_merge(['id' => $id], $request->all()),
        ]);
    }

    public function attachInmueble(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Asociar inmueble a búsqueda pendiente de implementación.',
            'busqueda_id' => $id,
            'data' => $request->all(),
        ], 201);
    }

    public function detachInmueble(string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Eliminar asociación inmueble-búsqueda pendiente de implementación.',
            'busqueda_inmueble_id' => $id,
        ]);
    }
}
