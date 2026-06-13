<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CarlaMenusSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::where('email', 'carla@gmail.com')->first();

        if (! $usuario) {
            throw new \RuntimeException('El usuario carla@gmail.com no existe. Ejecuta UsuarioSeeder primero.');
        }

        $now = Carbon::now();

        $tipoCasa = $this->catalogId('catalogo_tipos_inmueble', 'Casa');
        $tipoDepto = $this->catalogId('catalogo_tipos_inmueble', 'Departamento');
        $zonaCentro = $this->catalogId('catalogo_zonas', 'Centro');
        $zonaNorte = $this->catalogId('catalogo_zonas', 'Norte');
        $operacionVenta = $this->catalogId('catalogo_operaciones', 'Venta');
        $operacionRenta = $this->catalogId('catalogo_operaciones', 'Renta');
        $estadoSi = $this->catalogId('catalogo_amc_estados', 'Si');
        $monedaMxn = $this->catalogId('catalogo_monedas', 'MXN', 'codigo');
        $etapaCaptacion = $this->catalogId('catalogo_etapas', 'Captacion');
        $etapaPromocion = $this->catalogId('catalogo_etapas', 'Promocion');
        $accionContacto = $this->catalogId('catalogo_acciones', 'Contacto inicial');
        $accionVisita = $this->catalogId('catalogo_acciones', 'Visita programada');
        $estadoColocacion = $this->catalogId('catalogo_estados_colocacion', 'Negociacion');

        $asesorId = $this->firstOrCreate('asesores', [
            'email' => 'asesor.carla@demo.test',
        ], [
            'nombre' => 'Asesor Carla',
            'telefono' => '555-2100',
            'activo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $interesadoCapId = $this->firstOrCreate('interesados', [
            'email' => 'interesado.carla.cap@demo.test',
        ], [
            'nombre' => 'Interesado Carla Captacion',
            'telefono' => '555-2101',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $interesadoColId = $this->firstOrCreate('interesados', [
            'email' => 'interesado.carla.col@demo.test',
        ], [
            'nombre' => 'Interesado Carla Colocacion',
            'telefono' => '555-2102',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $interesadoVisId = $this->firstOrCreate('interesados', [
            'email' => 'interesado.carla.vis@demo.test',
        ], [
            'nombre' => 'Interesado Carla Visitas',
            'telefono' => '555-2103',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $interesadoPasarId = $this->firstOrCreate('interesados', [
            'email' => 'interesado.carla.pasar@demo.test',
        ], [
            'nombre' => 'Interesado Carla Pasar Info',
            'telefono' => '555-2104',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $interesadoCaptadoId = $this->firstOrCreate('interesados', [
            'email' => 'interesado.carla.captado@demo.test',
        ], [
            'nombre' => 'Interesado Carla Captado',
            'telefono' => '555-2105',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ---------------- Captacion (tablas actuales) ----------------
        $clienteCapId = $this->firstOrCreate('clientes', [
            'email' => 'carla.captacion@demo.test',
        ], [
            'usuario_id' => $usuario->id,
            'nombre' => 'Cliente Captacion Carla',
            'telefono' => '555-2201',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inmuebleCapId = $this->firstOrCreate('inmuebles', [
            'cliente_id' => $clienteCapId,
            'direccion' => 'Calle Captacion 101',
        ], [
            'descripcion' => 'Casa captacion demo de Carla.',
            'tipo_id' => $tipoCasa,
            'zona_id' => $zonaCentro,
            'operacion_id' => $operacionVenta,
            'amc_estado_id' => $estadoSi,
            'valor_estimado' => 2400000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $captacionId = $this->firstOrCreate('captaciones', [
            'cliente_id' => $clienteCapId,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'En proceso',
            'fecha_inicio' => $now->copy()->subDays(3)->toDateString(),
            'fecha_fin' => null,
            'notas' => 'Captación demo para Carla.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('historial_acciones', [
            'cliente_id' => $clienteCapId,
            'inmueble_id' => $inmuebleCapId,
            'interesado_id' => $interesadoCapId,
            'usuario_id' => $usuario->id,
            'asesor_id' => $asesorId,
            'accion_id' => $accionContacto,
        ], [
            'etapa_id' => $etapaCaptacion,
            'notas' => 'Contacto inicial captación Carla.',
            'fecha_accion' => $now->copy()->subDays(2)->toDateString(),
            'fecha_proxima_accion' => $now->copy()->addDays(3)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ---------------- Colocacion (tablas nuevas) ----------------
        $clienteColId = $this->firstOrCreate('colocacion_clientes', [
            'email' => 'carla.colocacion@demo.test',
        ], [
            'usuario_id' => $usuario->id,
            'nombre' => 'Cliente Colocacion Carla',
            'telefono' => '555-2202',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inmuebleColId = $this->firstOrCreate('colocacion_inmuebles', [
            'cliente_id' => $clienteColId,
            'direccion' => 'Av. Colocacion 202',
        ], [
            'descripcion' => 'Departamento demo colocacion Carla.',
            'tipo_id' => $tipoDepto,
            'zona_id' => $zonaNorte,
            'operacion_id' => $operacionRenta,
            'amc_estado_id' => $estadoSi,
            'valor_estimado' => 18000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $busquedaColId = $this->firstOrCreate('colocacion_busquedas_clientes', [
            'cliente_id' => $clienteColId,
            'descripcion' => 'Departamento en renta zona norte.',
        ], [
            'operacion_id' => $operacionRenta,
            'tipo_inmueble_id' => $tipoDepto,
            'zona_id' => $zonaNorte,
            'presupuesto' => 20000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('colocacion_busqueda_inmueble', [
            'busqueda_id' => $busquedaColId,
            'inmueble_id' => $inmuebleColId,
        ], [
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $colocacionId = $this->firstOrCreate('colocacion_registros', [
            'busqueda_id' => $busquedaColId,
            'inmueble_id' => $inmuebleColId,
        ], [
            'asesor_id' => $asesorId,
            'estado_id' => $estadoColocacion,
            'notas' => 'Colocación demo Carla.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('colocacion_historial_acciones', [
            'cliente_id' => $clienteColId,
            'inmueble_id' => $inmuebleColId,
            'interesado_id' => $interesadoColId,
            'usuario_id' => $usuario->id,
            'asesor_id' => $asesorId,
            'accion_id' => $accionVisita,
        ], [
            'etapa_id' => $etapaPromocion,
            'notas' => 'Visita programada colocacion Carla.',
            'fecha_accion' => $now->copy()->subDay()->toDateString(),
            'fecha_proxima_accion' => $now->copy()->addDays(2)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ---------------- Visitas (tablas nuevas) ----------------
        $clienteVisId = $this->firstOrCreate('visitas_clientes', [
            'email' => 'carla.visitas@demo.test',
        ], [
            'usuario_id' => $usuario->id,
            'nombre' => 'Cliente Visitas Carla',
            'telefono' => '555-2203',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inmuebleVisId = $this->firstOrCreate('visitas_inmuebles', [
            'cliente_id' => $clienteVisId,
            'direccion' => 'Calle Visitas 303',
        ], [
            'descripcion' => 'Casa para visita demo Carla.',
            'tipo_id' => $tipoCasa,
            'zona_id' => $zonaCentro,
            'operacion_id' => $operacionVenta,
            'amc_estado_id' => $estadoSi,
            'valor_estimado' => 3100000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $visitaFecha = $now->copy()->subDays(2)->setTime(11, 0);
        $visitaId = $this->firstOrCreate('visitas_registros', [
            'inmueble_id' => $inmuebleVisId,
            'cliente_id' => $clienteVisId,
            'interesado_id' => $interesadoVisId,
            'fecha' => $visitaFecha->toDateTimeString(),
        ], [
            'estado' => 'Agendada',
            'notas' => 'Visita demo Carla.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('visitas_acciones', [
            'visita_id' => $visitaId,
            'usuario_id' => $usuario->id,
        ], [
            'fecha' => $now->copy()->subDay()->setTime(9, 30)->toDateTimeString(),
            'descripcion' => 'Confirmación de visita Carla.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('visitas_historial_acciones', [
            'cliente_id' => $clienteVisId,
            'inmueble_id' => $inmuebleVisId,
            'interesado_id' => $interesadoVisId,
            'usuario_id' => $usuario->id,
            'asesor_id' => $asesorId,
            'accion_id' => $accionVisita,
        ], [
            'etapa_id' => $etapaPromocion,
            'notas' => 'Seguimiento visita Carla.',
            'fecha_accion' => $now->copy()->subDays(2)->toDateString(),
            'fecha_proxima_accion' => $now->copy()->addDays(1)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ---------------- Pasar Informacion (tablas nuevas) ----------------
        $clientePasarId = $this->firstOrCreate('pasar_informacion_clientes', [
            'email' => 'carla.pasar@demo.test',
        ], [
            'usuario_id' => $usuario->id,
            'nombre' => 'Cliente Pasar Info Carla',
            'telefono' => '555-2204',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inmueblePasarId = $this->firstOrCreate('pasar_informacion_inmuebles', [
            'cliente_id' => $clientePasarId,
            'direccion' => 'Av. Pasar 404',
        ], [
            'descripcion' => 'Propiedad para compartir info Carla.',
            'tipo_id' => $tipoDepto,
            'zona_id' => $zonaCentro,
            'operacion_id' => $operacionVenta,
            'amc_estado_id' => $estadoSi,
            'valor_estimado' => 1500000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pasarId = $this->firstOrCreate('pasar_informacion_registros', [
            'cliente_id' => $clientePasarId,
            'inmueble_id' => $inmueblePasarId,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'En seguimiento',
            'comentarios' => 'Registro pasar informacion Carla.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('pasar_informacion_historial_acciones', [
            'cliente_id' => $clientePasarId,
            'inmueble_id' => $inmueblePasarId,
            'interesado_id' => $interesadoPasarId,
            'usuario_id' => $usuario->id,
            'asesor_id' => $asesorId,
            'accion_id' => $accionVisita,
        ], [
            'etapa_id' => $etapaPromocion,
            'notas' => 'Seguimiento pasar informacion Carla.',
            'fecha_accion' => $now->copy()->subDays(4)->toDateString(),
            'fecha_proxima_accion' => $now->copy()->addDays(4)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ---------------- Inmuebles Captados (tablas nuevas) ----------------
        $clienteCaptadoId = $this->firstOrCreate('inmuebles_captados_clientes', [
            'email' => 'carla.captado@demo.test',
        ], [
            'usuario_id' => $usuario->id,
            'nombre' => 'Cliente Captado Carla',
            'telefono' => '555-2205',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inmuebleCaptadoId = $this->firstOrCreate('inmuebles_captados_inmuebles', [
            'cliente_id' => $clienteCaptadoId,
            'direccion' => 'Calle Captado 505',
        ], [
            'descripcion' => 'Inmueble captado demo Carla.',
            'tipo_id' => $tipoCasa,
            'zona_id' => $zonaNorte,
            'operacion_id' => $operacionVenta,
            'amc_estado_id' => $estadoSi,
            'valor_estimado' => 2750000,
            'moneda_id' => $monedaMxn,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $captacionCaptadoId = $this->firstOrCreate('inmuebles_captados_captaciones', [
            'cliente_id' => $clienteCaptadoId,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'Activo',
            'fecha_inicio' => $now->copy()->subDays(5)->toDateString(),
            'fecha_fin' => null,
            'notas' => 'Captación interna inmuebles captados Carla.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $captadoId = $this->firstOrCreate('inmuebles_captados_registros', [
            'inmueble_id' => $inmuebleCaptadoId,
            'captacion_id' => $captacionCaptadoId,
        ], [
            'estado' => 'En seguimiento',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firstOrCreate('inmuebles_captados_historial_acciones', [
            'cliente_id' => $clienteCaptadoId,
            'inmueble_id' => $inmuebleCaptadoId,
            'interesado_id' => $interesadoCaptadoId,
            'usuario_id' => $usuario->id,
            'asesor_id' => $asesorId,
            'accion_id' => $accionContacto,
        ], [
            'etapa_id' => $etapaCaptacion,
            'notas' => 'Seguimiento inmueble captado Carla.',
            'fecha_accion' => $now->copy()->subDays(1)->toDateString(),
            'fecha_proxima_accion' => $now->copy()->addDays(5)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command?->info('Flujo Carla generado: captacion #' . $captacionId . ', colocacion #' . $colocacionId . ', visita #' . $visitaId . ', pasar info #' . $pasarId . ', captado #' . $captadoId . '.');
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
