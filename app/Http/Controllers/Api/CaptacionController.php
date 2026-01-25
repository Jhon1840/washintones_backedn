<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Captacion;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use App\Services\CrmEntityResolver;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaptacionController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly CrmEntityResolver $resolver
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 25), 1);
        $usuario = $this->requireUsuario($request);

        $captaciones = Captacion::query()
            ->with(['cliente.ultimoInmueble', 'usuario'])
            ->where('usuario_id', $usuario->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Captacion $captacion) {
                return [
                    'id' => $captacion->id,
                    'cliente' => $captacion->cliente->nombre ?? '—',
                    'inmueble' => $captacion->cliente?->ultimoInmueble?->direccion ?? '—',
                    'estado' => $captacion->estado,
                    'fecha_inicio' => $captacion->fecha_inicio,
                    'fecha_fin' => $captacion->fecha_fin,
                    'notas' => $captacion->notas,
                    'usuario' => $captacion->usuario->nombre ?? '—',
                ];
            });

        return response()->json([
            'message' => 'Captaciones recuperadas.',
            'data' => $captaciones,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $data = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'valor_estimado' => ['nullable'],
            'descripcion' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
            'tipo' => ['nullable', 'string', 'max:120'],
            'estado_amc' => ['nullable', 'string', 'max:120'],
            'proxima_accion' => ['nullable', 'string', 'max:255'],
            'fecha_proxima_accion' => ['nullable', 'string'],
            'fecha_inicio' => ['nullable', 'string'],
        ]);

        $cliente = $this->resolver->resolveCliente(
            $data['cliente_nombre'],
            $data['telefono'] ?? null,
            $data['correo'] ?? null,
            $usuario->id
        );

        $inmueble = $this->resolver->resolveInmueble(
            $data['direccion'],
            $cliente,
            [
                'descripcion' => $data['descripcion'] ?? null,
                'notas' => $data['notas'] ?? null,
                'valor_estimado' => $data['valor_estimado'] ?? null,
                'tipo' => $data['tipo'] ?? null,
                'estado_amc' => $data['estado_amc'] ?? null,
            ],
            $usuario->id
        );

        $captacion = Captacion::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => $data['estado_amc'] ?? 'En proceso',
            'fecha_inicio' => $this->resolver->normalizeDate($data['fecha_inicio'] ?? null)
                ?? now()->toDateString(),
            'fecha_fin' => null,
            'notas' => $data['notas'] ?? $data['descripcion'] ?? null,
        ]);

        $this->resolver->registerHistorialAccion($cliente, $inmueble, $usuario->id, [
            'etapa' => 'Captacion',
            'accion' => $data['proxima_accion'] ?? null,
            'notas' => $data['descripcion'] ?? $data['notas'] ?? null,
            'fecha_accion' => $captacion->fecha_inicio?->toDateString(),
            'fecha_proxima_accion' => $data['fecha_proxima_accion'] ?? null,
            'interesado_nombre' => $data['cliente_nombre'],
            'interesado_telefono' => $data['telefono'] ?? null,
            'interesado_email' => $data['correo'] ?? null,
        ]);

        return response()->json([
            'message' => 'Captación registrada correctamente.',
            'data' => [
                'id' => $captacion->id,
                'cliente' => $cliente->nombre,
                'inmueble' => $inmueble->direccion,
                'estado' => $captacion->estado,
                'fecha_inicio' => $captacion->fecha_inicio,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $captacion = Captacion::with(['cliente.ultimoInmueble', 'usuario'])
            ->where('usuario_id', $usuario->id)
            ->find($id);

        if (! $captacion) {
            return response()->json([
                'message' => 'Captación no encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de captación recuperado.',
            'data' => [
                'id' => $captacion->id,
                'cliente' => $captacion->cliente->nombre ?? '—',
                'inmueble' => $captacion->cliente?->ultimoInmueble?->direccion ?? '—',
                'estado' => $captacion->estado,
                'inicio' => $captacion->fecha_inicio,
                'fin' => $captacion->fecha_fin,
                'notas' => $captacion->notas,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $captacion = Captacion::where('usuario_id', $usuario->id)->find($id);

        if (! $captacion) {
            return response()->json([
                'message' => 'Captación no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'estado' => ['sometimes', 'string', 'max:120'],
            'fecha_fin' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ]);

        if (isset($data['estado'])) {
            $captacion->estado = $data['estado'];
        }

        if (array_key_exists('fecha_fin', $data)) {
            $captacion->fecha_fin = $this->resolver->normalizeDate($data['fecha_fin']) ?? $captacion->fecha_fin;
        }

        if (array_key_exists('notas', $data)) {
            $captacion->notas = $data['notas'];
        }

        $captacion->save();

        return response()->json([
            'message' => 'Captación actualizada.',
            'data' => $captacion,
        ]);
    }

    public function historial(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $captacion = Captacion::find($id);
        $resolvedFromHistorial = false;

        $timeline = collect();
        $clienteId = $captacion?->cliente_id;

        if ($captacion && $captacion->usuario_id !== $usuario->id) {
            return response()->json([
                'message' => 'Captación no encontrada.',
            ], 404);
        }

        if ($clienteId) {
            $timeline = $this->timelineForCliente((int) $clienteId, $usuario->id);
        }

        if ($timeline->isEmpty()) {
            $historial = DB::table('historial_acciones')
                ->select(['id', 'cliente_id'])
                ->where('id', $id)
                ->where('usuario_id', $usuario->id)
                ->whereNull('deleted_at')
                ->first();

            if (! $historial) {
                if (! $captacion) {
                    return response()->json([
                        'message' => 'Captación no encontrada.',
                    ], 404);
                }

                return response()->json([
                    'message' => 'Historial de captación recuperado.',
                    'captacion_id' => (int) $captacion->id,
                    'resuelto_desde_historial' => false,
                    'data' => [],
                ]);
            }

            $resolvedFromHistorial = true;
            $clienteId = $historial->cliente_id;
            if (! $captacion) {
                $captacion = Captacion::where('cliente_id', $clienteId)->first();
                if ($captacion && $captacion->usuario_id !== $usuario->id) {
                    $captacion = null;
                }
            }

            $timeline = $this->timelineForCliente((int) $clienteId, $usuario->id);
        }

        return response()->json([
            'message' => 'Historial de captación recuperado.',
            'captacion_id' => $captacion ? (int) $captacion->id : null,
            'resuelto_desde_historial' => $resolvedFromHistorial,
            'data' => $timeline,
        ]);
    }

    public function historialGlobal(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min($limit, 1000));

        $capSubquery = DB::table('captaciones')
            ->select(['cliente_id', DB::raw('MAX(id) as captacion_id')])
            ->where('usuario_id', $usuario->id)
            ->groupBy('cliente_id');

        $timeline = $this->timelineBaseQuery($usuario->id)
            ->leftJoinSub($capSubquery, 'cap', 'cap.cliente_id', '=', 'ha.cliente_id')
            ->select(array_merge(
                $this->timelineSelectColumns(),
                ['cap.captacion_id as captacion_id']
            ))
            ->orderByDesc('ha.fecha_accion')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial global de captaciones recuperado.',
            'data' => $timeline,
        ]);
    }

    public function historialPapelera(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $activeClientes = DB::table('historial_acciones')
            ->select('cliente_id')
            ->where('usuario_id', $usuario->id)
            ->whereNull('deleted_at');

        $capSubquery = DB::table('captaciones')
            ->select(['cliente_id', DB::raw('MAX(id) as captacion_id')])
            ->where('usuario_id', $usuario->id)
            ->groupBy('cliente_id');

        $rows = DB::table('historial_acciones as ha')
            ->join('clientes as c', 'c.id', '=', 'ha.cliente_id')
            ->join('inmuebles as i', 'i.id', '=', 'ha.inmueble_id')
            ->join('catalogo_etapas as ce', 'ce.id', '=', 'ha.etapa_id')
            ->join('catalogo_acciones as ca', 'ca.id', '=', 'ha.accion_id')
            ->leftJoin('interesados as interes', 'interes.id', '=', 'ha.interesado_id')
            ->leftJoin('asesores as a', 'a.id', '=', 'ha.asesor_id')
            ->leftJoinSub($capSubquery, 'cap', 'cap.cliente_id', '=', 'ha.cliente_id')
            ->select([
                'ha.id',
                'ha.inmueble_id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'ce.nombre as etapa',
                'ca.nombre as accion',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
                'interes.nombre as interesado',
                'a.nombre as asesor',
                'cap.captacion_id as captacion_id',
            ])
            ->where('ha.usuario_id', $usuario->id)
            ->whereNotNull('ha.deleted_at')
            ->whereNotIn('ha.cliente_id', $activeClientes)
            ->orderByDesc('ha.fecha_accion')
            ->orderByDesc('ha.id')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $captacionId = (int) ($row->captacion_id ?? 0);
            if ($captacionId < 1) {
                continue;
            }

            $entry = (array) $row;

            if (! array_key_exists($captacionId, $groups)) {
                $groups[$captacionId] = [
                    'captacion_id' => $captacionId,
                    'total_acciones' => 1,
                    'latest' => $entry,
                ];
                continue;
            }

            $groups[$captacionId]['total_acciones'] += 1;
            if ($this->isMoreRecentRow($row, $groups[$captacionId]['latest'])) {
                $groups[$captacionId]['latest'] = $entry;
            }
        }

        return response()->json([
            'message' => 'Papelera de captacion recuperada.',
            'data' => array_values($groups),
        ]);
    }

    public function softDeleteHistorialGrupo(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $captacionId = (int) $request->input('captacion_id');

        if ($captacionId < 1) {
            return response()->json([
                'message' => 'Captacion invalida.',
            ], 422);
        }

        $captacion = Captacion::where('id', $captacionId)
            ->where('usuario_id', $usuario->id)
            ->first();

        if (! $captacion) {
            return response()->json([
                'message' => 'Captacion no encontrada.',
            ], 404);
        }

        $updated = DB::table('historial_acciones')
            ->where('cliente_id', $captacion->cliente_id)
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
        $captacionId = (int) $request->input('captacion_id');

        if ($captacionId < 1) {
            return response()->json([
                'message' => 'Captacion invalida.',
            ], 422);
        }

        $captacion = Captacion::where('id', $captacionId)
            ->where('usuario_id', $usuario->id)
            ->first();

        if (! $captacion) {
            return response()->json([
                'message' => 'Captacion no encontrada.',
            ], 404);
        }

        $updated = DB::table('historial_acciones')
            ->where('cliente_id', $captacion->cliente_id)
            ->where('usuario_id', $usuario->id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        return response()->json([
            'message' => $updated ? 'Historial restaurado.' : 'Historial no encontrado.',
            'total' => $updated,
        ]);
    }

    private function timelineForCliente(int $clienteId, ?int $usuarioId = null)
    {
        return $this->timelineBaseQuery($usuarioId)
            ->select($this->timelineSelectColumns())
            ->where('ha.cliente_id', $clienteId)
            ->orderByDesc('ha.fecha_accion')
            ->get();
    }

    private function timelineBaseQuery(?int $usuarioId = null)
    {
        $query = DB::table('historial_acciones as ha')
            ->join('clientes as c', 'c.id', '=', 'ha.cliente_id')
            ->join('inmuebles as i', 'i.id', '=', 'ha.inmueble_id')
            ->join('catalogo_etapas as ce', 'ce.id', '=', 'ha.etapa_id')
            ->join('catalogo_acciones as ca', 'ca.id', '=', 'ha.accion_id')
            ->leftJoin('interesados as interes', 'interes.id', '=', 'ha.interesado_id')
            ->leftJoin('asesores as a', 'a.id', '=', 'ha.asesor_id');
        $query->whereNull('ha.deleted_at');

        if ($usuarioId !== null) {
            $query->where('ha.usuario_id', $usuarioId);
        }

        return $query;
    }

    private function timelineSelectColumns(): array
    {
        return [
            'ha.id',
            'ha.inmueble_id',
            'c.nombre as cliente',
            'i.direccion as inmueble',
            'ce.nombre as etapa',
            'ca.nombre as accion',
            'ha.notas',
            'ha.fecha_accion',
            'ha.fecha_proxima_accion',
            'interes.nombre as interesado',
            'a.nombre as asesor',
        ];
    }

    public function proximasAcciones(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = max((int) $request->query('limit', 5), 1);

        $acciones = DB::table('historial_acciones as ha')
            ->join('clientes as c', 'c.id', '=', 'ha.cliente_id')
            ->join('inmuebles as i', 'i.id', '=', 'ha.inmueble_id')
            ->join('catalogo_acciones as ca', 'ca.id', '=', 'ha.accion_id')
            ->leftJoinSub(
                DB::table('captaciones')
                    ->select(['cliente_id', DB::raw('MAX(id) as captacion_id')])
                    ->where('usuario_id', $usuario->id)
                    ->groupBy('cliente_id'),
                'cap',
                'cap.cliente_id',
                '=',
                'ha.cliente_id'
            )
            ->select([
                'ha.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'ca.nombre as accion',
                'ha.fecha_proxima_accion',
                'ha.notas',
                'cap.captacion_id as captacion_id',
            ])
            ->where('ha.usuario_id', $usuario->id)
            ->whereNull('ha.deleted_at')
            ->whereDate('ha.fecha_proxima_accion', '>=', now()->toDateString())
            ->orderBy('ha.fecha_proxima_accion')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'nombre' => $row->accion,
                    'cliente' => $row->cliente,
                    'inmueble' => $row->inmueble,
                    'fecha' => $row->fecha_proxima_accion,
                    'notas' => $row->notas,
                    'captacion_id' => $row->captacion_id,
                ];
            });

        return response()->json([
            'message' => 'Próximas acciones recuperadas.',
            'data' => $acciones,
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
}
