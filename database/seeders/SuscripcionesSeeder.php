<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SuscripcionesSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = Usuario::where('activo', true)
            ->where('es_admin', false)
            ->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $planes = Plan::where('activo', true)->get();

        if ($planes->isEmpty()) {
            return;
        }

        $today = Carbon::today();

        // Valores de ejemplo para planes ilimitados (decididos por “admin”)
        // Incluye un caso gratis (0) y algunos montos bajos.
        $preciosIlimitadosDemo = [0, 300, 500, 800];

        for ($i = 0; $i < 7; $i++) {
            $fechaInicio = $today->copy()->subDays($i)->toDateString();
            $usuario = $usuarios[$i % $usuarios->count()];
            $plan = $planes[$i % $planes->count()];

            $fechaFin = null;

            if ($plan->duracion_dias !== null) {
                $fechaFin = Carbon::parse($fechaInicio)
                    ->addDays($plan->duracion_dias)
                    ->toDateString();
            }

            if ($plan->duracion_dias === null) {
                // En la realidad el admin define el precio de cada ilimitado.
                // Para demo usamos una lista fija de valores razonables.
                $precioMensual = (float) $preciosIlimitadosDemo[$i % count($preciosIlimitadosDemo)];
            } else {
                // Para planes con duración, usamos el precio del plan.
                $precioMensual = (float) $plan->precio;
            }

            Suscripcion::updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'plan_id' => $plan->id,
                    'fecha_inicio' => $fechaInicio,
                ],
                [
                    'estado' => 'activa',
                    'precio_mensual' => $precioMensual,
                    'fecha_fin' => $fechaFin,
                    'ultimo_pago' => $fechaInicio,
                ]
            );
        }
    }
}
