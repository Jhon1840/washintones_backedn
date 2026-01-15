<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Visita;
use App\Models\VisitaAccion;
use App\Services\AuthTokenService;
use App\Services\CrmEntityResolver;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitaController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly CrmEntityResolver $resolver
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 25), 1);

        $visitas = Visita::query()
            ->with(['cliente', 'inmueble'])
            ->orderByDesc('fecha')
            ->limit($limit)
            ->get()
            ->map(function (Visita $visita) {
                return [
                    'id' => $visita->id,
                    'cliente' => $visita->cliente->nombre ?? '—',
                    'inmueble' => $visita->inmueble->direccion ?? '—',
                    'estado' => $visita->estado,
                    'fecha' => $visita->fecha,
                    'notas' => $visita->notas,
                ];
            });

        return response()->json([
            'message' => 'Visitas recuperadas.',
            'data' => $visitas,
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
            $data['cliente_nombre'],
            $data['telefono'] ?? null,
            null,
            $usuario->id
        );

        $inmueble = $this->resolver->resolveInmueble(
            $data['inmueble'] ?? ('Inmueble visita ' . $cliente->nombre),
            $cliente,
            ['descripcion' => $data['notas'] ?? null]
        );

        $interesado = $this->resolver->resolveInteresado(
            $data['interesado_nombre'],
            $data['telefono'] ?? null,
            null
        );

        $fecha = $this->parseDateTime($data['fecha_contacto'] ?? null) ?? now()->toDateTimeString();

        $visita = Visita::create([
            'inmueble_id' => $inmueble->id,
            'cliente_id' => $cliente->id,
            'fecha' => $fecha,
            'estado' => 'Programada',
            'notas' => $data['notas'] ?? null,
        ]);

        if (! empty($data['proxima_accion']) || ! empty($data['notas'])) {
            VisitaAccion::create([
                'visita_id' => $visita->id,
                'usuario_id' => $usuario->id,
                'fecha' => $this->parseDateTime($data['fecha_proxima_accion'] ?? null) ?? now()->toDateTimeString(),
                'descripcion' => $data['proxima_accion'] ?? ($data['notas'] ?? 'Seguimiento'),
            ]);
        }

        $this->resolver->registerHistorialAccion($cliente, $inmueble, $usuario->id, [
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
                'id' => $visita->id,
                'cliente' => $cliente->nombre,
                'inmueble' => $inmueble->direccion,
            ],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $visita = Visita::with(['cliente', 'inmueble'])->find($id);

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle recuperado.',
            'data' => [
                'id' => $visita->id,
                'cliente' => $visita->cliente->nombre ?? '—',
                'inmueble' => $visita->inmueble->direccion ?? '—',
                'estado' => $visita->estado,
                'fecha' => $visita->fecha,
                'notas' => $visita->notas,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $visita = Visita::find($id);

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'estado' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string'],
        ]);

        if (array_key_exists('estado', $data)) {
            $visita->estado = $data['estado'];
        }

        if (array_key_exists('notas', $data)) {
            $visita->notas = $data['notas'];
        }

        $visita->save();

        return response()->json([
            'message' => 'Visita actualizada.',
            'data' => $visita,
        ]);
    }

    public function historialGlobal(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min($limit, 1000));

        $acciones = DB::table('visita_acciones as va')
            ->join('visitas as v', 'v.id', '=', 'va.visita_id')
            ->join('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->join('inmuebles as i', 'i.id', '=', 'v.inmueble_id')
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
            ->orderByDesc('va.fecha')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial global de visitas recuperado.',
            'data' => $acciones,
        ]);
    }

    public function acciones(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $visita = Visita::find($id);

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $acciones = VisitaAccion::where('visita_id', $visita->id)
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
        $visita = Visita::find($id);

        if (! $visita) {
            return response()->json([
                'message' => 'Visita no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'descripcion' => ['required', 'string'],
            'fecha' => ['nullable', 'string'],
        ]);

        $accion = VisitaAccion::create([
            'visita_id' => $visita->id,
            'usuario_id' => $usuario->id,
            'fecha' => $this->parseDateTime($data['fecha'] ?? null) ?? now()->toDateTimeString(),
            'descripcion' => $data['descripcion'],
        ]);

        return response()->json([
            'message' => 'Acción registrada.',
            'data' => $accion,
        ], 201);
    }

    public function updateAccion(Request $request, string $id): JsonResponse
    {
        $accion = VisitaAccion::find($id);

        if (! $accion) {
            return response()->json([
                'message' => 'Acción no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'descripcion' => ['nullable', 'string'],
            'fecha' => ['nullable', 'string'],
        ]);

        if (array_key_exists('descripcion', $data)) {
            $accion->descripcion = $data['descripcion'] ?? $accion->descripcion;
        }

        if (array_key_exists('fecha', $data)) {
            $accion->fecha = $this->parseDateTime($data['fecha']) ?? $accion->fecha;
        }

        $accion->save();

        return response()->json([
            'message' => 'Acción actualizada.',
            'data' => $accion,
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
