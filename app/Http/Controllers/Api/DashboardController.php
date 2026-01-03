<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        $accionesHoy = DB::table('historial_acciones')
            ->whereDate('fecha_accion', $today)
            ->count();

        $visitasHoy = DB::table('visitas')
            ->whereDate('fecha', $today)
            ->count();

        $tareasVencidas = DB::table('tareas')
            ->where('completado', false)
            ->whereDate('fecha', '<', $today)
            ->count();

        $captacionesActivas = DB::table('captaciones')
            ->whereNull('fecha_fin')
            ->count();

        $cerradaId = DB::table('catalogo_estados_colocacion')
            ->whereRaw('LOWER(nombre) = ?', ['cerrada'])
            ->value('id');

        $colocacionesActivas = DB::table('colocaciones')
            ->when($cerradaId, fn ($query) => $query->where('estado_id', '!=', $cerradaId))
            ->count();

        return response()->json([
            'acciones_hoy' => $accionesHoy,
            'visitas_hoy' => $visitasHoy,
            'tareas_vencidas' => $tareasVencidas,
            'captaciones_activas' => $captacionesActivas,
            'colocaciones_activas' => $colocacionesActivas,
            'message' => 'Dashboard actualizado.',
        ]);
    }
}
