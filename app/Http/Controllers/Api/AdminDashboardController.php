<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
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

        $inicioSemana = now()->subDays(6)->startOfDay();
        $finSemana = now()->endOfDay();

        $totalesSemana = DB::table('suscripciones')
            ->whereBetween('fecha_inicio', [$inicioSemana->toDateString(), $finSemana->toDateString()])
            ->selectRaw('DATE(fecha_inicio) as fecha, SUM(precio_mensual) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $serieSemanal = [];
        $labels = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicioSemana->copy()->addDays($i);
            $key = $fecha->toDateString();
            $total = (float) ($totalesSemana[$key] ?? 0);
            $label = $labels[$fecha->dayOfWeekIso - 1];

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
