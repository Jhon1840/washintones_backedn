<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CarlaNotificacionTareaSeeder extends Seeder
{
    /**
     * Crea una tarea para Carla con vencimiento mañana (para probar notificaciones).
     */
    public function run(): void
    {
        $usuario = Usuario::where('email', 'carla@gmail.com')->first();
        if (! $usuario) {
            throw new \RuntimeException('El usuario carla@gmail.com no existe. Ejecuta UsuarioSeeder primero.');
        }

        $historialId = DB::table('historial_acciones')
            ->where('usuario_id', $usuario->id)
            ->value('id');

        if (! $historialId) {
            throw new \RuntimeException('No existe historial_acciones para Carla. Ejecuta CarlaMenusSeeder primero.');
        }

        $tipoId = DB::table('catalogo_tipos_tarea')->value('id');
        if (! $tipoId) {
            throw new \RuntimeException('catalogo_tipos_tarea está vacío. Ejecuta CatalogoSeeder primero.');
        }

        $fecha = Carbon::now()->addDay()->toDateString();
        $now = Carbon::now();

        DB::table('tareas')->updateOrInsert(
            [
                'historial_id' => $historialId,
                'descripcion' => 'Tarea prueba notificacion (vence mañana)',
                'fecha' => $fecha,
            ],
            [
                'tipo_id' => $tipoId,
                'completado' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $this->command?->info('Tarea de prueba para Carla creada (vence mañana).');
    }
}
