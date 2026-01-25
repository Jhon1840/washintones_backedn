<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FlujoUsuarioSeeder extends Seeder
{
    /**
     * Flujo completo para el usuario "flujo@demo.test".
     */
    public function run(): void
    {
        $usuario = Usuario::where('email', 'flujo@demo.test')->first();

        if (! $usuario) {
            throw new \RuntimeException('El usuario flujo@demo.test no existe. Ejecuta UsuarioSeeder primero.');
        }

        $now = Carbon::now();

        $clienteId = $this->firstOrCreate('clientes', [
            'email' => 'cliente.flujo@demo.test',
        ], [
            'usuario_id' => $usuario->id,
            'nombre' => 'Cliente Flujo Demo',
            'telefono' => '555-0606',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tipoCasa = $this->catalogId('catalogo_tipos_inmueble', 'Casa');
        $zonaNorte = $this->catalogId('catalogo_zonas', 'Norte');
        $operacionVenta = $this->catalogId('catalogo_operaciones', 'Venta');
        $estadoSi = $this->catalogId('catalogo_amc_estados', 'Si');
        $monedaMxn = $this->catalogId('catalogo_monedas', 'MXN', 'codigo');

        $inmuebleId = $this->firstOrCreate('inmuebles', [
            'cliente_id' => $clienteId,
            'direccion' => 'Av. Flujo 123, Zona Norte',
        ], [
            'descripcion' => 'Casa de 3 recámaras con patio y cochera.',
            'tipo_id' => $tipoCasa,
            'zona_id' => $zonaNorte,
            'operacion_id' => $operacionVenta,
            'amc_estado_id' => $estadoSi,
            'valor_estimado' => 3200000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $captacionId = $this->firstOrCreate('captaciones', [
            'cliente_id' => $clienteId,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'En proceso',
            'fecha_inicio' => $now->copy()->subDays(4)->toDateString(),
            'fecha_fin' => null,
            'notas' => 'Captación inicial para flujo demo.',
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
            'email' => 'interesado.flujo@demo.test',
        ], [
            'nombre' => 'Interesado Flujo',
            'telefono' => '555-0610',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $visitaFecha = $now->copy()->subDays(2)->setTime(10, 30);
        $visitaId = $this->firstOrCreate('visitas', [
            'inmueble_id' => $inmuebleId,
            'cliente_id' => $clienteId,
            'fecha' => $visitaFecha->toDateTimeString(),
        ], [
            'estado' => 'Agendada',
            'notas' => 'Visita programada para el flujo demo.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('visita_acciones', [
            'visita_id' => $visitaId,
            'usuario_id' => $usuario->id,
        ], [
            'fecha' => $now->copy()->subDay()->setTime(9, 0)->toDateTimeString(),
            'descripcion' => 'Confirmación de visita y envío de ubicación.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('pasar_informacion', [
            'cliente_id' => $clienteId,
            'inmueble_id' => $inmuebleId,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'En seguimiento',
            'comentarios' => 'Información compartida con el cliente flujo.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $asesorId = $this->firstOrCreate('asesores', [
            'email' => 'asesor.flujo@demo.test',
        ], [
            'nombre' => 'Asesor Flujo',
            'telefono' => '555-0620',
            'activo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $busquedaId = $this->firstOrCreate('busquedas_clientes', [
            'cliente_id' => $clienteId,
            'descripcion' => 'Casa familiar con patio en zona norte.',
        ], [
            'operacion_id' => $operacionVenta,
            'tipo_inmueble_id' => $tipoCasa,
            'zona_id' => $zonaNorte,
            'presupuesto' => 3300000,
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
            'notas' => 'Proceso de colocación en negociación.',
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
            'accion_id' => $accionVisita,
        ], [
            'etapa_id' => $etapaPromocion,
            'notas' => 'Registro de seguimiento para flujo demo.',
            'fecha_accion' => $now->copy()->subDay()->toDateString(),
            'fecha_proxima_accion' => $now->copy()->addDays(2)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command?->info('Flujo demo para flujo@demo.test generado (colocación #' . $colocacionId . ').');
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
