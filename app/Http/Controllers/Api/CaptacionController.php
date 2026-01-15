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

        $captaciones = Captacion::query()
            ->with(['inmueble.cliente', 'usuario'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Captacion $captacion) {
                return [
                    'id' => $captacion->id,
                    'cliente' => $captacion->inmueble->cliente->nombre ?? '—',
                    'inmueble' => $captacion->inmueble->direccion ?? '—',
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
        ]);

        $cliente = $this->resolver->resolveCliente(
            $data['cliente_nombre'],
            $data['telefono'] ?? null,
            $data['correo'] ?? null,
            $usuario->id
        );

        $inmueble = $this->resolver->resolveInmueble($data['direccion'], $cliente, [
            'descripcion' => $data['descripcion'] ?? null,
            'notas' => $data['notas'] ?? null,
            'valor_estimado' => $data['valor_estimado'] ?? null,
            'tipo' => $data['tipo'] ?? null,
            'estado_amc' => $data['estado_amc'] ?? null,
        ]);

        $captacion = Captacion::create([
            'inmueble_id' => $inmueble->id,
            'usuario_id' => $usuario->id,
            'estado' => $data['estado_amc'] ?? 'En proceso',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'notas' => $data['notas'] ?? $data['descripcion'] ?? null,
        ]);

        $this->resolver->registerHistorialAccion($cliente, $inmueble, $usuario->id, [
            'etapa' => 'Captacion',
            'accion' => $data['proxima_accion'] ?? null,
            'notas' => $data['descripcion'] ?? $data['notas'] ?? null,
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
            ],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $captacion = Captacion::with(['inmueble.cliente', 'usuario'])->find($id);

        if (! $captacion) {
            return response()->json([
                'message' => 'Captación no encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de captación recuperado.',
            'data' => [
                'id' => $captacion->id,
                'cliente' => $captacion->inmueble->cliente->nombre ?? '—',
                'inmueble' => $captacion->inmueble->direccion ?? '—',
                'estado' => $captacion->estado,
                'inicio' => $captacion->fecha_inicio,
                'fin' => $captacion->fecha_fin,
                'notas' => $captacion->notas,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $captacion = Captacion::find($id);

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
        $inmuebleId = $captacion?->inmueble_id;

        if ($captacion && $captacion->usuario_id !== $usuario->id) {
            return response()->json([
                'message' => 'Captación no encontrada.',
            ], 404);
        }

        if ($inmuebleId) {
            $timeline = $this->timelineForInmueble((int) $inmuebleId, $usuario->id);
        }

        if ($timeline->isEmpty()) {
            $historial = DB::table('historial_acciones')
                ->select(['id', 'inmueble_id'])
                ->where('id', $id)
                ->where('usuario_id', $usuario->id)
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
            $inmuebleId = $historial->inmueble_id;
            if (! $captacion) {
                $captacion = Captacion::where('inmueble_id', $inmuebleId)->first();
                if ($captacion && $captacion->usuario_id !== $usuario->id) {
                    $captacion = null;
                }
            }

            $timeline = $this->timelineForInmueble((int) $inmuebleId, $usuario->id);
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

        $timeline = $this->timelineBaseQuery($usuario->id)
            ->join('captaciones as cap', 'cap.inmueble_id', '=', 'ha.inmueble_id')
            ->select(array_merge(
                $this->timelineSelectColumns(),
                ['cap.id as captacion_id']
            ))
            ->orderByDesc('ha.fecha_accion')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial global de captaciones recuperado.',
            'data' => $timeline,
        ]);
    }

    private function timelineForInmueble(int $inmuebleId, ?int $usuarioId = null)
    {
        return $this->timelineBaseQuery($usuarioId)
            ->select($this->timelineSelectColumns())
            ->where('ha.inmueble_id', $inmuebleId)
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
            ->leftJoin('captaciones as cap', 'cap.inmueble_id', '=', 'ha.inmueble_id')
            ->select([
                'ha.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'ca.nombre as accion',
                'ha.fecha_proxima_accion',
                'ha.notas',
                'cap.id as captacion_id',
            ])
            ->where('ha.usuario_id', $usuario->id)
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
}
