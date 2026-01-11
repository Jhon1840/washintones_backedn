<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanesSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            [
                'nombre' => 'Free',
                'duracion_dias' => 7,
                'precio' => 0,
                'activo' => true,
            ],
            [
                'nombre' => 'Mensual',
                'duracion_dias' => 30,
                'precio' => 50,
                'activo' => true,
            ],
            [
                'nombre' => 'Bimestral',
                'duracion_dias' => 60,
                'precio' => 90,
                'activo' => true,
            ],
            [
                'nombre' => 'Trimestral',
                'duracion_dias' => 90,
                'precio' => 130,
                'activo' => true,
            ],
            [
                'nombre' => 'Ilimitado',
                'duracion_dias' => null,
                'precio' => 0,
                'activo' => true,
            ],
        ];

        foreach ($planes as $data) {
            Plan::updateOrCreate(
                ['nombre' => $data['nombre']],
                $data
            );
        }
    }
}
