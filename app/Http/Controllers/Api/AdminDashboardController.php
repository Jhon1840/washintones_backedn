<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $totalUsuarios = DB::table('usuarios')->count();
        $today = now()->toDateString();

        $suscripcionesActivas = DB::table('suscripciones')
            ->where('estado', 'activa')
            ->where(function ($query) use ($today) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $today);
            });

        $gananciasMes = (float) $suscripcionesActivas->sum('precio_mensual');
        $gananciasAnio = $gananciasMes * 12;

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $inicioSemana = $dateFrom
            ? now()->parse($dateFrom)->startOfDay()
            : now()->subDays(6)->startOfDay();
        $finSemana = $dateTo
            ? now()->parse($dateTo)->endOfDay()
            : now()->endOfDay();

        $totalesSemana = DB::table('suscripciones')
            ->whereBetween('fecha_inicio', [$inicioSemana->toDateString(), $finSemana->toDateString()])
            ->selectRaw('DATE(fecha_inicio) as fecha, SUM(precio_mensual) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $serieSemanal = [];
        $days = $inicioSemana->diffInDays($finSemana) + 1;
        $days = max(1, min($days, 62));

        for ($i = 0; $i < $days; $i++) {
            $fecha = $inicioSemana->copy()->addDays($i);
            $key = $fecha->toDateString();
            $total = (float) ($totalesSemana[$key] ?? 0);
            $label = $fecha->format('d/m');

            $serieSemanal[] = [
                'fecha' => $key,
                'label' => $label,
                'total' => $total,
            ];
        }

        return response()->json([
            'total_usuarios' => $totalUsuarios,
            'ganancias_mes' => (float) $gananciasMes,
            'ganancias_anio' => (float) $gananciasAnio,
            'serie_semanal' => $serieSemanal,
            'message' => 'Dashboard admin actualizado.',
        ]);
    }
}
