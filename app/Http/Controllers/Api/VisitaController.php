<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use App\Services\ModuleEntityResolver;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitaController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly ModuleEntityResolver $resolver
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 25), 1);
        $usuario = $this->requireUsuario($request);

        $visitas = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->join('visitas_inmuebles as i', 'i.id', '=', 'v.inmueble_id')
            ->select([
                'v.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'v.estado',
                'v.fecha',
                'v.notas',
            ])
            ->where('c.usuario_id', $usuario->id)
            ->orderByDesc('v.fecha')
            ->limit($limit)
            ->get();

        $items = $visitas->map(function ($row) {
            return [
                'id' => $row->id,
                'cliente' => $row->cliente ?? '—',
                'inmueble' => $row->inmueble ?? '—',
                'estado' => $row->estado,
                'fecha' => $row->fecha,
                'notas' => $row->notas,
            ];
        });

        return response()->json([
            'message' => 'Visitas recuperadas.',
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $data = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'inmueble' => ['nullable', 'string', 'max:255'],
            'interesado_nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'asesor' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
            'proxima_accion' => ['nullable', 'string', 'max:255'],
            'fecha_contacto' => ['nullable', 'string'],
            'fecha_proxima_accion' => ['nullable', 'string'],
        ]);

        $cliente = $this->resolver->resolveCliente(
            'visitas_clientes',
            $data['cliente_nombre'],
            $data['telefono'] ?? null,
            null,
            $usuario->id
        );

        $inmueble = $this->resolver->resolveInmueble(
            'visitas_inmuebles',
            $cliente->id,
            $data['inmueble'] ?? ('Inmueble visita ' . $cliente->nombre),
            ['descripcion' => $data['notas'] ?? null],
            $usuario->id
        );

        $interesado = $this->resolver->resolveInteresado(
            $data['interesado_nombre'],
            $data['telefono'] ?? null,
            null
        );

        $fecha = $this->parseDateTime($data['fecha_contacto'] ?? null) ?? now()->toDateTimeString();

        $visitaId = DB::table('visitas_registros')->insertGetId([
            'inmueble_id' => $inmueble->id,
            'cliente_id' => $cliente->id,
            'fecha' => $fecha,
            'estado' => 'Programada',
            'notas' => $data['notas'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! empty($data['proxima_accion']) || ! empty($data['notas'])) {
            DB::table('visitas_acciones')->insert([
                'visita_id' => $visitaId,
                'usuario_id' => $usuario->id,
                'fecha' => $this->parseDateTime($data['fecha_proxima_accion'] ?? null) ?? now()->toDateTimeString(),
                'descripcion' => $data['proxima_accion'] ?? ($data['notas'] ?? 'Seguimiento'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->resolver->registerHistorialAccion('visitas_historial_acciones', $cliente->id, $inmueble->id, $usuario->id, [
            'etapa' => 'Promocion',
            'accion' => $data['proxima_accion'] ?? null,
            'notas' => $data['notas'] ?? null,
            'fecha_accion' => $data['fecha_contacto'] ?? null,
            'fecha_proxima_accion' => $data['fecha_proxima_accion'] ?? null,
            'interesado_nombre' => $interesado->nombre,
            'interesado_telefono' => $interesado->telefono,
            'asesor_nombre' => $data['asesor'] ?? null,
        ]);

        return response()->json([
            'message' => 'Visita registrada.',
            'data' => [
                'id' => $visitaId,
                'cliente' => $cliente->nombre,
                'inmueble' => $inmueble->direccion,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $visita = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->join('visitas_inmuebles as i', 'i.id', '=', 'v.inmueble_id')
            ->select([
                'v.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'v.estado',
                'v.fecha',
                'v.notas',
            ])
            ->where('c.usuario_id', $usuario->id)
            ->where('v.id', $id)
            ->first();

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle recuperado.',
            'data' => [
                'id' => $visita->id,
                'cliente' => $visita->cliente ?? '—',
                'inmueble' => $visita->inmueble ?? '—',
                'estado' => $visita->estado,
                'fecha' => $visita->fecha,
                'notas' => $visita->notas,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $visita = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->where('c.usuario_id', $usuario->id)
            ->where('v.id', $id)
            ->select(['v.id'])
            ->first();

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'estado' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string'],
        ]);

        $updates = [];
        if (array_key_exists('estado', $data)) {
            $updates['estado'] = $data['estado'];
        }
        if (array_key_exists('notas', $data)) {
            $updates['notas'] = $data['notas'];
        }
        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('visitas_registros')->where('id', $id)->update($updates);
        }

        $updated = DB::table('visitas_registros')->where('id', $id)->first();

        return response()->json([
            'message' => 'Visita actualizada.',
            'data' => $updated,
        ]);
    }

    public function historialGlobal(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min($limit, 1000));

        $acciones = DB::table('visitas_acciones as va')
            ->join('visitas_registros as v', 'v.id', '=', 'va.visita_id')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->join('visitas_inmuebles as i', 'i.id', '=', 'v.inmueble_id')
            ->select([
                'va.id',
                'va.visita_id',
                'va.descripcion',
                'va.fecha',
                'va.created_at',
                'c.nombre as cliente',
                'c.telefono as telefono',
                'i.direccion as inmueble',
            ])
            ->where('va.usuario_id', $usuario->id)
            ->where('c.usuario_id', $usuario->id)
            ->orderByDesc('va.fecha')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial global de visitas recuperado.',
            'data' => $acciones,
        ]);
    }

    public function historialPapelera(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $activeInmuebles = DB::table('visitas_historial_acciones')
            ->select('inmueble_id')
            ->where('usuario_id', $usuario->id)
            ->whereNull('deleted_at');

        $rows = DB::table('visitas_historial_acciones as ha')
            ->join('visitas_registros as v', function ($join) {
                $join->on('v.inmueble_id', '=', 'ha.inmueble_id')
                    ->on('v.cliente_id', '=', 'ha.cliente_id');
            })
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->join('visitas_inmuebles as i', 'i.id', '=', 'v.inmueble_id')
            ->select([
                'ha.id',
                'ha.inmueble_id',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
                'v.id as visita_id',
                'v.estado',
                'c.nombre as cliente',
                'i.direccion as inmueble',
            ])
            ->where('ha.usuario_id', $usuario->id)
            ->where('c.usuario_id', $usuario->id)
            ->whereNotNull('ha.deleted_at')
            ->whereNotIn('ha.inmueble_id', $activeInmuebles)
            ->orderByDesc('ha.fecha_accion')
            ->orderByDesc('ha.id')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $visitaId = (int) ($row->visita_id ?? 0);
            if ($visitaId < 1) {
                continue;
            }

            $entry = (array) $row;

            if (! array_key_exists($visitaId, $groups)) {
                $groups[$visitaId] = [
                    'visita_id' => $visitaId,
                    'total_acciones' => 1,
                    'latest' => $entry,
                ];
                continue;
            }

            $groups[$visitaId]['total_acciones'] += 1;
            if ($this->isMoreRecentRow($row, $groups[$visitaId]['latest'])) {
                $groups[$visitaId]['latest'] = $entry;
            }
        }

        return response()->json([
            'message' => 'Papelera de visitas recuperada.',
            'data' => array_values($groups),
        ]);
    }

    public function softDeleteHistorialGrupo(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $visitaId = (int) $request->input('visita_id');

        if ($visitaId < 1) {
            return response()->json([
                'message' => 'Visita inválida.',
            ], 422);
        }

        $visita = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->select(['v.id', 'v.inmueble_id'])
            ->where('c.usuario_id', $usuario->id)
            ->where('v.id', $visitaId)
            ->first();

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $updated = DB::table('visitas_historial_acciones')
            ->where('inmueble_id', $visita->inmueble_id)
            ->where('usuario_id', $usuario->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        return response()->json([
            'message' => $updated ? 'Historial enviado a papelera.' : 'Historial no encontrado.',
            'total' => $updated,
        ]);
    }

    public function restoreHistorialGrupo(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $visitaId = (int) $request->input('visita_id');

        if ($visitaId < 1) {
            return response()->json([
                'message' => 'Visita inválida.',
            ], 422);
        }

        $visita = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->select(['v.id', 'v.inmueble_id'])
            ->where('c.usuario_id', $usuario->id)
            ->where('v.id', $visitaId)
            ->first();

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $updated = DB::table('visitas_historial_acciones')
            ->where('inmueble_id', $visita->inmueble_id)
            ->where('usuario_id', $usuario->id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        return response()->json([
            'message' => $updated ? 'Historial restaurado.' : 'Historial no encontrado.',
            'total' => $updated,
        ]);
    }

    public function acciones(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $visita = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->where('c.usuario_id', $usuario->id)
            ->where('v.id', $id)
            ->select(['v.id'])
            ->first();

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $acciones = DB::table('visitas_acciones')
            ->where('visita_id', $visita->id)
            ->where('usuario_id', $usuario->id)
            ->orderByDesc('fecha')
            ->get();

        return response()->json([
            'message' => 'Acciones de visita recuperadas.',
            'visita_id' => (int) $id,
            'data' => $acciones,
        ]);
    }

    public function storeAccion(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $visita = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->where('c.usuario_id', $usuario->id)
            ->where('v.id', $id)
            ->select(['v.id'])
            ->first();

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'descripcion' => ['required', 'string'],
            'fecha' => ['nullable', 'string'],
        ]);

        $accionId = DB::table('visitas_acciones')->insertGetId([
            'visita_id' => $visita->id,
            'usuario_id' => $usuario->id,
            'fecha' => $this->parseDateTime($data['fecha'] ?? null) ?? now()->toDateTimeString(),
            'descripcion' => $data['descripcion'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accion = DB::table('visitas_acciones')->where('id', $accionId)->first();

        return response()->json([
            'message' => 'Acción registrada.',
            'data' => $accion,
        ], 201);
    }

    public function updateAccion(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $accion = DB::table('visitas_acciones')
            ->where('usuario_id', $usuario->id)
            ->where('id', $id)
            ->first();

        if (! $accion) {
            return response()->json([
                'message' => 'Acción no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'descripcion' => ['nullable', 'string'],
            'fecha' => ['nullable', 'string'],
        ]);

        $updates = [];
        if (array_key_exists('descripcion', $data)) {
            $updates['descripcion'] = $data['descripcion'] ?? $accion->descripcion;
        }
        if (array_key_exists('fecha', $data)) {
            $updates['fecha'] = $this->parseDateTime($data['fecha']) ?? $accion->fecha;
        }
        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('visitas_acciones')->where('id', $id)->update($updates);
        }

        $updated = DB::table('visitas_acciones')->where('id', $id)->first();

        return response()->json([
            'message' => 'Acción actualizada.',
            'data' => $updated,
        ]);
    }

    private function requireUsuario(Request $request): Usuario
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        if (! $usuario) {
            throw new HttpResponseException(response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401));
        }

        return $usuario;
    }

    private function isMoreRecentRow(object $candidate, array $current): bool
    {
        $candidateDate = $candidate->fecha_accion ? strtotime($candidate->fecha_accion) : 0;
        $currentDate = isset($current['fecha_accion'])
            ? strtotime($current['fecha_accion'])
            : 0;

        if ($candidateDate === $currentDate) {
            return ((int) $candidate->id) > ((int) ($current['id'] ?? 0));
        }

        return $candidateDate > $currentDate;
    }

    private function parseDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
