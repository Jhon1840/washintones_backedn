<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SuscripcionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 50), 1);

        $suscripciones = Suscripcion::query()
            ->with('plan')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Suscripciones recuperadas.',
            'data' => $suscripciones,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'plan_id' => ['required', 'exists:planes,id'],
            'estado' => ['sometimes', 'string', 'max:50'],
            'precio_mensual' => ['sometimes', 'numeric', 'min:0'],
            'fecha_inicio' => ['sometimes', 'date'],
            'ultimo_pago' => ['sometimes', 'nullable', 'date'],
        ]);

        $plan = Plan::find($data['plan_id']);
        $fechaInicio = $data['fecha_inicio'] ?? now()->toDateString();
        $fechaFin = $this->resolveFechaFin($plan, $fechaInicio);
        $precio = $this->resolvePrecioMensual($plan, $data['precio_mensual'] ?? null, null);

        if ($precio === null) {
            return response()->json([
                'message' => 'Debe indicar precio_mensual para planes ilimitados.',
            ], 422);
        }

        $suscripcion = Suscripcion::create([
            'usuario_id' => $data['usuario_id'],
            'plan_id' => $data['plan_id'],
            'estado' => $data['estado'] ?? 'activa',
            'precio_mensual' => $precio,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'ultimo_pago' => $data['ultimo_pago'] ?? null,
        ]);

        return response()->json([
            'message' => 'Suscripcion creada correctamente.',
            'data' => $suscripcion->load('plan'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $suscripcion = Suscripcion::with('plan')->find($id);

        if (! $suscripcion) {
            return response()->json([
                'message' => 'Suscripcion no encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de suscripcion recuperado.',
            'data' => $suscripcion,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $suscripcion = Suscripcion::find($id);

        if (! $suscripcion) {
            return response()->json([
                'message' => 'Suscripcion no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'usuario_id' => ['sometimes', 'exists:usuarios,id'],
            'plan_id' => ['sometimes', 'exists:planes,id'],
            'estado' => ['sometimes', 'string', 'max:50'],
            'precio_mensual' => ['sometimes', 'numeric', 'min:0'],
            'fecha_inicio' => ['sometimes', 'date'],
            'ultimo_pago' => ['sometimes', 'nullable', 'date'],
        ]);

        $planId = $data['plan_id'] ?? $suscripcion->plan_id;

        if (! $planId) {
            return response()->json([
                'message' => 'La suscripcion debe tener un plan asociado.',
            ], 422);
        }

        $plan = Plan::find($planId);
        $fechaInicio = $data['fecha_inicio'] ?? ($suscripcion->fecha_inicio?->toDateString() ?? now()->toDateString());
        $fechaFin = $this->resolveFechaFin($plan, $fechaInicio);
        $precio = $this->resolvePrecioMensual($plan, $data['precio_mensual'] ?? null, $suscripcion->precio_mensual);
        $estado = array_key_exists('estado', $data)
            ? $data['estado']
            : $this->resolveEstadoByFechaFin($fechaFin);

        if ($precio === null) {
            return response()->json([
                'message' => 'Debe indicar precio_mensual para planes ilimitados.',
            ], 422);
        }

        $suscripcion->fill([
            'usuario_id' => $data['usuario_id'] ?? $suscripcion->usuario_id,
            'plan_id' => $planId,
            'estado' => $estado,
            'precio_mensual' => $precio,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'ultimo_pago' => array_key_exists('ultimo_pago', $data) ? $data['ultimo_pago'] : $suscripcion->ultimo_pago,
        ])->save();

        return response()->json([
            'message' => 'Suscripcion actualizada.',
            'data' => $suscripcion->load('plan'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $suscripcion = Suscripcion::find($id);

        if (! $suscripcion) {
            return response()->json([
                'message' => 'Suscripcion no encontrada.',
            ], 404);
        }

        $suscripcion->delete();

        return response()->json([
            'message' => 'Suscripcion eliminada.',
        ]);
    }

    private function resolveFechaFin(Plan $plan, string $fechaInicio): ?string
    {
        if ($plan->duracion_dias === null) {
            return null;
        }

        return Carbon::parse($fechaInicio)->addDays($plan->duracion_dias)->toDateString();
    }

    private function resolvePrecioMensual(Plan $plan, ?float $precioSolicitado, ?float $precioActual): ?float
    {
        if ($plan->duracion_dias === null) {
            return $precioSolicitado ?? $precioActual;
        }

        return (float) $plan->precio;
    }

    private function resolveEstadoByFechaFin(?string $fechaFin): string
    {
        if ($fechaFin === null) {
            return 'activa';
        }

        return Carbon::parse($fechaFin)->isBefore(now()->startOfDay())
            ? 'inactiva'
            : 'activa';
    }
}
