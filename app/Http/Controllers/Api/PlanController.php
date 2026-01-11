<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 50), 1);

        $planes = Plan::query()
            ->orderBy('nombre')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Planes recuperados.',
            'data' => $planes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'duracion_dias' => ['nullable', 'integer', 'min:1'],
            'precio' => ['required', 'numeric', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        if (! array_key_exists('activo', $data)) {
            $data['activo'] = true;
        }

        $plan = Plan::create($data);

        return response()->json([
            'message' => 'Plan creado correctamente.',
            'data' => $plan,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $plan = Plan::find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Plan no encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de plan recuperado.',
            'data' => $plan,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $plan = Plan::find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Plan no encontrado.',
            ], 404);
        }

        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:255'],
            'duracion_dias' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'precio' => ['sometimes', 'numeric', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $plan->fill($data)->save();

        return response()->json([
            'message' => 'Plan actualizado.',
            'data' => $plan,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $plan = Plan::find($id);

        if (! $plan) {
            return response()->json([
                'message' => 'Plan no encontrado.',
            ], 404);
        }

        $plan->delete();

        return response()->json([
            'message' => 'Plan eliminado.',
        ]);
    }
}
