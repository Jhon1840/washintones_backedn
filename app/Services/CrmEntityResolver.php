<?php

namespace App\Services;

use App\Models\Asesor;
use App\Models\Busqueda;
use App\Models\BusquedaInmueble;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Interesado;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmEntityResolver
{
    public function resolveCliente(
        string $nombre,
        ?string $telefono = null,
        ?string $email = null,
        ?int $usuarioId = null
    ): Cliente
    {
        $nombre = trim($nombre);
        $baseQuery = Cliente::query();

        if ($usuarioId !== null) {
            $baseQuery->where('usuario_id', $usuarioId);
        }

        if ($email) {
            $cliente = (clone $baseQuery)->where('email', $email)->first();
            if ($cliente) {
                return $this->syncClienteData($cliente, $telefono, $email);
            }
        }

        if ($nombre !== '') {
            $cliente = (clone $baseQuery)->where('nombre', $nombre)->first();
            if ($cliente) {
                return $this->syncClienteData($cliente, $telefono, $email);
            }
        }

        return Cliente::create([
            'usuario_id' => $usuarioId,
            'nombre' => $nombre !== '' ? $nombre : 'Cliente app ' . now()->format('YmdHis'),
            'telefono' => $telefono ?: '000-0000',
            'email' => $email ?: $this->placeholderEmail($nombre),
        ]);
    }

    public function resolveInmueble(string $direccion, Cliente $cliente, array $attributes = []): Inmueble
    {
        $direccion = trim($direccion);

        if ($direccion === '') {
            $direccion = 'Inmueble sin dirección ' . now()->format('YmdHis');
        }

        $existing = Inmueble::where('cliente_id', $cliente->id)
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

        return Inmueble::create([
            'cliente_id' => $cliente->id,
            'direccion' => $direccion,
            'descripcion' => $descripcion !== '' ? $descripcion : 'Registro creado desde app móvil.',
            'tipo_id' => $tipoId,
            'zona_id' => $zonaId,
            'operacion_id' => $operacionId,
            'amc_estado_id' => $estadoId,
            'valor_estimado' => $valor,
            'moneda_id' => $monedaId,
        ]);
    }

    public function resolveInteresado(string $nombre, ?string $telefono = null, ?string $email = null): Interesado
    {
        $nombre = trim($nombre);

        if ($email) {
            $interesado = Interesado::where('email', $email)->first();
            if ($interesado) {
                return $this->syncInteresadoData($interesado, $telefono, $email);
            }
        }

        if ($nombre !== '') {
            $interesado = Interesado::where('nombre', $nombre)->first();
            if ($interesado) {
                return $this->syncInteresadoData($interesado, $telefono, $email);
            }
        }

        return Interesado::create([
            'nombre' => $nombre !== '' ? $nombre : 'Interesado ' . now()->format('YmdHis'),
            'telefono' => $telefono ?: '000-0000',
            'email' => $email ?: $this->placeholderEmail($nombre, prefix: 'interesado'),
        ]);
    }

    public function resolveAsesor(?string $nombre, ?string $telefono = null, ?string $email = null): ?Asesor
    {
        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            return null;
        }

        $asesor = Asesor::where('nombre', $nombre)->first();

        if ($asesor) {
            $updates = [];
            if ($telefono && $asesor->telefono !== $telefono) {
                $updates['telefono'] = $telefono;
            }
            if ($email && $asesor->email !== $email) {
                $updates['email'] = $email;
            }

            if ($updates) {
                $asesor->fill($updates)->save();
            }

            return $asesor;
        }

        return Asesor::create([
            'nombre' => $nombre,
            'telefono' => $telefono ?: '000-0000',
            'email' => $email ?: $this->placeholderEmail($nombre, prefix: 'asesor'),
            'activo' => true,
        ]);
    }

    public function ensureBusqueda(
        Cliente $cliente,
        array $payload,
        int $operacionId,
        int $tipoId,
        int $zonaId,
        int $monedaId,
        ?Inmueble $inmueble = null
    ): Busqueda {
        $descripcion = trim((string) ($payload['descripcion_busqueda'] ?? ''));
        if ($descripcion === '') {
            $descripcion = 'Búsqueda generada desde app móvil.';
        }

        $busqueda = Busqueda::where('cliente_id', $cliente->id)
            ->where('descripcion', $descripcion)
            ->latest('id')
            ->first();

        if (! $busqueda) {
            $busqueda = Busqueda::create([
                'cliente_id' => $cliente->id,
                'descripcion' => $descripcion,
                'operacion_id' => $operacionId,
                'tipo_inmueble_id' => $tipoId,
                'zona_id' => $zonaId,
                'presupuesto' => $this->parseAmount($payload['presupuesto'] ?? null),
                'moneda_id' => $monedaId,
            ]);
        }

        if ($inmueble) {
            BusquedaInmueble::firstOrCreate([
                'busqueda_id' => $busqueda->id,
                'inmueble_id' => $inmueble->id,
            ]);
        }

        return $busqueda;
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

    public function registerHistorialAccion(
        Cliente $cliente,
        Inmueble $inmueble,
        int $usuarioId,
        array $payload
    ): void {
        if (($payload['accion'] ?? null) === null
            && ($payload['notas'] ?? null) === null
            && ($payload['fecha_proxima_accion'] ?? null) === null) {
            return;
        }

        $interesadoNombre = trim((string) ($payload['interesado_nombre'] ?? 'Contacto ' . $cliente->nombre));
        $interesado = $this->resolveInteresado(
            $interesadoNombre,
            $payload['interesado_telefono'] ?? null,
            $payload['interesado_email'] ?? null
        );

        $asesorNombre = trim((string) ($payload['asesor_nombre'] ?? $payload['interesado_nombre'] ?? $cliente->nombre));
        if ($asesorNombre === '') {
            $asesorNombre = 'Asesor ' . $cliente->nombre;
        }

        $asesor = $this->resolveAsesor(
            $asesorNombre,
            $payload['asesor_telefono'] ?? null,
            $payload['asesor_email'] ?? null
        );

        if (! $asesor) {
            $asesor = $this->resolveAsesor('Asesor ' . $cliente->nombre);
        }

        $etapaId = $this->catalogId('catalogo_etapas', $payload['etapa'] ?? null);
        $accionId = $this->catalogId('catalogo_acciones', $payload['accion'] ?? null);

        $fechaAccion = $this->normalizeDate($payload['fecha_accion'] ?? null) ?? now()->toDateString();
        $fechaProxima = $this->normalizeDate($payload['fecha_proxima_accion'] ?? null) ?? now()->addDays(3)->toDateString();

        DB::table('historial_acciones')->insert([
            'cliente_id' => $cliente->id,
            'inmueble_id' => $inmueble->id,
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

    private function syncClienteData(Cliente $cliente, ?string $telefono, ?string $email): Cliente
    {
        $updates = [];

        if ($telefono && $cliente->telefono !== $telefono) {
            $updates['telefono'] = $telefono;
        }

        if ($email && $cliente->email !== $email) {
            $updates['email'] = $email;
        }

        if ($updates) {
            $cliente->fill($updates)->save();
        }

        return $cliente;
    }

    private function syncInteresadoData(Interesado $interesado, ?string $telefono, ?string $email): Interesado
    {
        $updates = [];

        if ($telefono && $interesado->telefono !== $telefono) {
            $updates['telefono'] = $telefono;
        }

        if ($email && $interesado->email !== $email) {
            $updates['email'] = $email;
        }

        if ($updates) {
            $interesado->fill($updates)->save();
        }

        return $interesado;
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
