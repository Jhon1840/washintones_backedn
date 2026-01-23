<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleEntityResolver
{
    public function resolveCliente(
        string $table,
        string $nombre,
        ?string $telefono = null,
        ?string $email = null,
        ?int $usuarioId = null
    ): object {
        $nombre = trim($nombre);
        $baseQuery = DB::table($table);

        if ($usuarioId !== null) {
            $baseQuery->where('usuario_id', $usuarioId);
        }

        if ($email) {
            $cliente = (clone $baseQuery)->where('email', $email)->first();
            if ($cliente) {
                return $this->syncClienteData($table, $cliente, $telefono, $email);
            }
        }

        if ($nombre !== '') {
            $cliente = (clone $baseQuery)->where('nombre', $nombre)->first();
            if ($cliente) {
                return $this->syncClienteData($table, $cliente, $telefono, $email);
            }
        }

        $id = DB::table($table)->insertGetId([
            'usuario_id' => $usuarioId,
            'nombre' => $nombre !== '' ? $nombre : 'Cliente app ' . now()->format('YmdHis'),
            'telefono' => $telefono ?: '000-0000',
            'email' => $email ?: $this->placeholderEmail($nombre),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table($table)->where('id', $id)->first();
    }

    public function resolveInmueble(string $table, int $clienteId, string $direccion, array $attributes = []): object
    {
        $direccion = trim($direccion);

        if ($direccion === '') {
            $direccion = 'Inmueble sin dirección ' . now()->format('YmdHis');
        }

        $existing = DB::table($table)
            ->where('cliente_id', $clienteId)
            ->where('direccion', $direccion)
            ->first();

        if ($existing) {
            return $existing;
        }

        $descripcion = trim((string) ($attributes['descripcion'] ?? $attributes['notas'] ?? ''));
        $valor = $this->parseAmount($attributes['valor_estimado'] ?? null);

        $tipoId = $attributes['tipo_id'] ?? $this->catalogId('catalogo_tipos_inmueble', $attributes['tipo'] ?? null);
        $zonaId = $attributes['zona_id'] ?? $this->catalogId('catalogo_zonas', $attributes['zona'] ?? null);
        $operacionId = $attributes['operacion_id'] ?? $this->catalogId('catalogo_operaciones', $attributes['operacion'] ?? null);
        $estadoId = $attributes['amc_estado_id'] ?? $this->catalogId('catalogo_amc_estados', $attributes['estado_amc'] ?? null);
        $monedaId = $attributes['moneda_id'] ?? $this->catalogId('catalogo_monedas', $attributes['moneda'] ?? 'MXN', 'codigo');

        $id = DB::table($table)->insertGetId([
            'cliente_id' => $clienteId,
            'direccion' => $direccion,
            'descripcion' => $descripcion !== '' ? $descripcion : 'Registro creado desde app móvil.',
            'tipo_id' => $tipoId,
            'zona_id' => $zonaId,
            'operacion_id' => $operacionId,
            'amc_estado_id' => $estadoId,
            'valor_estimado' => $valor,
            'moneda_id' => $monedaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table($table)->where('id', $id)->first();
    }

    public function resolveInteresado(string $nombre, ?string $telefono = null, ?string $email = null): object
    {
        $nombre = trim($nombre);

        if ($email) {
            $interesado = DB::table('interesados')->where('email', $email)->first();
            if ($interesado) {
                return $this->syncInteresadoData($interesado, $telefono, $email);
            }
        }

        if ($nombre !== '') {
            $interesado = DB::table('interesados')->where('nombre', $nombre)->first();
            if ($interesado) {
                return $this->syncInteresadoData($interesado, $telefono, $email);
            }
        }

        $id = DB::table('interesados')->insertGetId([
            'nombre' => $nombre !== '' ? $nombre : 'Interesado ' . now()->format('YmdHis'),
            'telefono' => $telefono ?: '000-0000',
            'email' => $email ?: $this->placeholderEmail($nombre, prefix: 'interesado'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('interesados')->where('id', $id)->first();
    }

    public function resolveAsesor(?string $nombre, ?string $telefono = null, ?string $email = null): ?object
    {
        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            return null;
        }

        $asesor = DB::table('asesores')->where('nombre', $nombre)->first();

        if ($asesor) {
            $updates = [];
            if ($telefono && $asesor->telefono !== $telefono) {
                $updates['telefono'] = $telefono;
            }
            if ($email && $asesor->email !== $email) {
                $updates['email'] = $email;
            }

            if ($updates) {
                DB::table('asesores')->where('id', $asesor->id)->update(array_merge($updates, [
                    'updated_at' => now(),
                ]));
                $asesor = DB::table('asesores')->where('id', $asesor->id)->first();
            }

            return $asesor;
        }

        $id = DB::table('asesores')->insertGetId([
            'nombre' => $nombre,
            'telefono' => $telefono ?: '000-0000',
            'email' => $email ?: $this->placeholderEmail($nombre, prefix: 'asesor'),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('asesores')->where('id', $id)->first();
    }

    public function ensureBusqueda(
        string $busquedaTable,
        string $busquedaInmuebleTable,
        int $clienteId,
        array $payload,
        int $operacionId,
        int $tipoId,
        int $zonaId,
        int $monedaId,
        ?int $inmuebleId = null
    ): object {
        $descripcion = trim((string) ($payload['descripcion_busqueda'] ?? ''));
        if ($descripcion === '') {
            $descripcion = 'Búsqueda generada desde app móvil.';
        }

        $busqueda = DB::table($busquedaTable)
            ->where('cliente_id', $clienteId)
            ->where('descripcion', $descripcion)
            ->orderByDesc('id')
            ->first();

        if (! $busqueda) {
            $id = DB::table($busquedaTable)->insertGetId([
                'cliente_id' => $clienteId,
                'descripcion' => $descripcion,
                'operacion_id' => $operacionId,
                'tipo_inmueble_id' => $tipoId,
                'zona_id' => $zonaId,
                'presupuesto' => $this->parseAmount($payload['presupuesto'] ?? null),
                'moneda_id' => $monedaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $busqueda = DB::table($busquedaTable)->where('id', $id)->first();
        }

        if ($inmuebleId) {
            DB::table($busquedaInmuebleTable)->updateOrInsert([
                'busqueda_id' => $busqueda->id,
                'inmueble_id' => $inmuebleId,
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $busqueda;
    }

    public function registerHistorialAccion(
        string $table,
        int $clienteId,
        int $inmuebleId,
        int $usuarioId,
        array $payload
    ): void {
        if (($payload['accion'] ?? null) === null
            && ($payload['notas'] ?? null) === null
            && ($payload['fecha_proxima_accion'] ?? null) === null) {
            return;
        }

        $interesadoNombre = trim((string) ($payload['interesado_nombre'] ?? 'Contacto ' . $clienteId));
        $interesado = $this->resolveInteresado(
            $interesadoNombre,
            $payload['interesado_telefono'] ?? null,
            $payload['interesado_email'] ?? null
        );

        $asesorNombre = trim((string) ($payload['asesor_nombre'] ?? $payload['interesado_nombre'] ?? 'Asesor'));
        if ($asesorNombre === '') {
            $asesorNombre = 'Asesor ' . $clienteId;
        }

        $asesor = $this->resolveAsesor(
            $asesorNombre,
            $payload['asesor_telefono'] ?? null,
            $payload['asesor_email'] ?? null
        );

        if (! $asesor) {
            $asesor = $this->resolveAsesor('Asesor ' . $clienteId);
        }

        $etapaId = $this->catalogId('catalogo_etapas', $payload['etapa'] ?? null);
        $accionId = $this->catalogId('catalogo_acciones', $payload['accion'] ?? null);

        $fechaAccion = $this->normalizeDate($payload['fecha_accion'] ?? null) ?? now()->toDateString();
        $fechaProxima = $this->normalizeDate($payload['fecha_proxima_accion'] ?? null) ?? now()->addDays(3)->toDateString();

        DB::table($table)->insert([
            'cliente_id' => $clienteId,
            'inmueble_id' => $inmuebleId,
            'interesado_id' => $interesado->id,
            'usuario_id' => $usuarioId,
            'asesor_id' => $asesor->id,
            'etapa_id' => $etapaId,
            'accion_id' => $accionId,
            'notas' => $payload['notas'] ?? ($payload['accion'] ?? 'Seguimiento'),
            'fecha_accion' => $fechaAccion,
            'fecha_proxima_accion' => $fechaProxima,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->toDateString();
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }

            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    public function catalogId(string $table, ?string $value = null, string $column = 'nombre'): int
    {
        $value = $value !== null ? trim($value) : null;

        if ($value !== null && $value !== '') {
            $prepared = $column === 'codigo'
                ? Str::upper($value)
                : Str::lower($value);

            $query = DB::table($table);

            if ($column === 'codigo') {
                $id = $query->where($column, $prepared)->value('id');
            } else {
                $id = $query
                    ->whereRaw('LOWER(' . $column . ') = ?', [$prepared])
                    ->value('id');
            }

            if ($id) {
                return (int) $id;
            }
        }

        $fallback = DB::table($table)->orderBy('id')->value('id');

        if (! $fallback) {
            throw new \RuntimeException("El catálogo {$table} no tiene registros.");
        }

        return (int) $fallback;
    }

    private function syncClienteData(string $table, object $cliente, ?string $telefono, ?string $email): object
    {
        $updates = [];

        if ($telefono && $cliente->telefono !== $telefono) {
            $updates['telefono'] = $telefono;
        }

        if ($email && $cliente->email !== $email) {
            $updates['email'] = $email;
        }

        if ($updates) {
            $updates['updated_at'] = now();
            DB::table($table)->where('id', $cliente->id)->update($updates);
        }

        return DB::table($table)->where('id', $cliente->id)->first();
    }

    private function syncInteresadoData(object $interesado, ?string $telefono, ?string $email): object
    {
        $updates = [];

        if ($telefono && $interesado->telefono !== $telefono) {
            $updates['telefono'] = $telefono;
        }

        if ($email && $interesado->email !== $email) {
            $updates['email'] = $email;
        }

        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('interesados')->where('id', $interesado->id)->update($updates);
        }

        return DB::table('interesados')->where('id', $interesado->id)->first();
    }

    private function placeholderEmail(string $base, string $prefix = 'cliente'): string
    {
        $slug = Str::slug($base !== '' ? $base : now()->format('YmdHis'));
        if ($slug === '') {
            $slug = Str::random(8);
        }

        return "{$prefix}-{$slug}@example.local";
    }

    private function parseAmount(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = (float) preg_replace('/[^0-9.]/', '', $value);
            return $clean ?: 0.0;
        }

        return 0.0;
    }
}
