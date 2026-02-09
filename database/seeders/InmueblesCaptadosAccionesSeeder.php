<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InmueblesCaptadosAccionesSeeder extends Seeder
{
    public function run(): void
    {
        $etapaId = DB::table('catalogo_etapas')
            ->whereRaw('LOWER(nombre) = ?', ['inmuebles captados'])
            ->value('id');

        if (! $etapaId) {
            $etapaId = DB::table('catalogo_etapas')->insertGetId([
                'nombre' => 'Inmuebles Captados',
            ]);
        }

        $acciones = [
            'Elaboración y edición de fotos y videos',
            'Subir al sistema',
            'Publicar en Marketplace',
            'Promocionar en Meta',
            'Hacer el arte y descripción',
            'Subir a los grupos de Whatsapp',
        ];

        foreach ($acciones as $accion) {
            DB::table('catalogo_acciones')->updateOrInsert(
                [
                    'etapa_id' => $etapaId,
                    'nombre' => $accion,
                ],
                []
            );
        }
    }
}
