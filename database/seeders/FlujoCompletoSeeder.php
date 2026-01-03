<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FlujoCompletoSeeder extends Seeder
{
    /**
     * Crea un flujo completo (captación -> colocación) para la usuaria base Carla.
     */
    public function run(): void
    {
        $usuario = Usuario::where('email', 'carla@gmail.com')->first();

        if (! $usuario) {
            throw new \RuntimeException('El usuario base carla@gmail.com no existe. Ejecuta UsuarioSeeder primero.');
        }

        $now = Carbon::now();

        $clienteId = $this->firstOrCreate('clientes', ['email' => 'contacto@aurora.mx'], [
            'nombre' => 'Residencial Aurora',
            'telefono' => '555-1111',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tipoDepartamento = $this->catalogId('catalogo_tipos_inmueble', 'Departamento');
        $zonaCentro = $this->catalogId('catalogo_zonas', 'Centro');
        $operacionVenta = $this->catalogId('catalogo_operaciones', 'Venta');
        $estadoActiva = $this->catalogId('catalogo_amc_estados', 'Activa');
        $monedaMxn = $this->catalogId('catalogo_monedas', 'MXN', 'codigo');

        $inmuebleId = $this->firstOrCreate('inmuebles', [
            'cliente_id' => $clienteId,
            'direccion' => 'Calle Aurora 742, Centro',
        ], [
            'descripcion' => 'Departamento amplio con terraza y coworking para residentes.',
            'tipo_id' => $tipoDepartamento,
            'zona_id' => $zonaCentro,
            'operacion_id' => $operacionVenta,
            'amc_estado_id' => $estadoActiva,
            'valor_estimado' => 9850000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $captacionId = $this->firstOrCreate('captaciones', [
            'inmueble_id' => $inmuebleId,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'En proceso',
            'fecha_inicio' => $now->copy()->subDays(5)->toDateString(),
            'fecha_fin' => null,
            'notas' => 'Carla registró la captación del nuevo desarrollo Residencial Aurora.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('inmuebles_captados', [
            'inmueble_id' => $inmuebleId,
            'captacion_id' => $captacionId,
        ], [
            'estado' => 'Activo',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $interesadoId = $this->firstOrCreate('interesados', [
            'email' => 'jorge.morales@clientes.mx',
        ], [
            'nombre' => 'Jorge Morales',
            'telefono' => '555-7777',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $visitaFecha = $now->copy()->subDays(2)->setTime(11, 0);
        $visitaId = $this->firstOrCreate('visitas', [
            'inmueble_id' => $inmuebleId,
            'cliente_id' => $clienteId,
            'fecha' => $visitaFecha->toDateTimeString(),
        ], [
            'estado' => 'Realizada',
            'notas' => 'Recorrido guiado por Carla con el interesado Jorge Morales.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('visita_acciones', [
            'visita_id' => $visitaId,
            'usuario_id' => $usuario->id,
        ], [
            'fecha' => $now->copy()->subDay()->setTime(9, 30)->toDateTimeString(),
            'descripcion' => 'Envió brochure digital y agenda nueva llamada de seguimiento.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('pasar_informacion', [
            'cliente_id' => $clienteId,
            'inmueble_id' => $inmuebleId,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'En seguimiento',
            'comentarios' => 'Carla compartió información detallada del inmueble con el cliente.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $asesorId = $this->firstOrCreate('asesores', [
            'email' => 'maria.gutierrez@freddy-demo.test',
        ], [
            'nombre' => 'Maria Gutierrez',
            'telefono' => '555-9090',
            'activo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $busquedaId = $this->firstOrCreate('busquedas_clientes', [
            'cliente_id' => $clienteId,
            'descripcion' => 'Departamento con terraza y amenidades para familia joven.',
        ], [
            'operacion_id' => $operacionVenta,
            'tipo_inmueble_id' => $tipoDepartamento,
            'zona_id' => $zonaCentro,
            'presupuesto' => 9900000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('busqueda_inmueble', [
            'busqueda_id' => $busquedaId,
            'inmueble_id' => $inmuebleId,
        ], [
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $estadoColocacion = $this->catalogId('catalogo_estados_colocacion', 'Negociacion');
        $colocacionId = $this->firstOrCreate('colocaciones', [
            'busqueda_id' => $busquedaId,
            'inmueble_id' => $inmuebleId,
        ], [
            'asesor_id' => $asesorId,
            'estado_id' => $estadoColocacion,
            'notas' => 'Se presentó oferta inicial y se negocian condiciones.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $etapaPromocion = $this->catalogId('catalogo_etapas', 'Promocion');
        $accionVisita = $this->catalogId('catalogo_acciones', 'Visita programada');

        $this->firstOrCreate('historial_acciones', [
            'cliente_id' => $clienteId,
            'inmueble_id' => $inmuebleId,
            'interesado_id' => $interesadoId,
            'usuario_id' => $usuario->id,
            'asesor_id' => $asesorId,
        ], [
            'etapa_id' => $etapaPromocion,
            'accion_id' => $accionVisita,
            'notas' => 'Carla registró la visita y programó el siguiente contacto.',
            'fecha_accion' => $now->copy()->subDay()->toDateString(),
            'fecha_proxima_accion' => $now->copy()->addDays(3)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command?->info('Flujo completo para Carla generado (colocación #' . $colocacionId . ').');
    }

    private function catalogId(string $table, string $value, string $column = 'nombre'): int
    {
        $id = DB::table($table)->where($column, $value)->value('id');

        if (! $id) {
            throw new \RuntimeException("No se encontró {$value} en {$table}");
        }

        return (int) $id;
    }

    private function firstOrCreate(string $table, array $conditions, array $values): int
    {
        $record = DB::table($table)->where($conditions)->first();

        if ($record) {
            return (int) $record->id;
        }

        return (int) DB::table($table)->insertGetId(array_merge($conditions, $values));
    }
}
