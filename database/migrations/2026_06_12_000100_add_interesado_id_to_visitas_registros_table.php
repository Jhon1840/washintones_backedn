<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visitas_registros', function (Blueprint $table) {
            $table->foreignId('interesado_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('interesados')
                ->nullOnDelete();
        });

        DB::table('visitas_registros')
            ->whereNull('interesado_id')
            ->orderBy('id')
            ->eachById(function (object $visita): void {
                $fechaVisita = Carbon::parse($visita->fecha);
                $historial = DB::table('visitas_historial_acciones')
                    ->where('cliente_id', $visita->cliente_id)
                    ->where('inmueble_id', $visita->inmueble_id)
                    ->orderByDesc('fecha_accion')
                    ->orderByDesc('id')
                    ->get(['interesado_id', 'fecha_accion']);

                $interesadoId = $historial
                    ->sortBy(function (object $accion) use ($fechaVisita) {
                        return abs($fechaVisita->diffInSeconds(Carbon::parse($accion->fecha_accion), false));
                    })
                    ->first()?->interesado_id;

                if ($interesadoId) {
                    DB::table('visitas_registros')
                        ->where('id', $visita->id)
                        ->update(['interesado_id' => $interesadoId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('visitas_registros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interesado_id');
        });
    }
};
