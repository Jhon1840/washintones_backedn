<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Captacion;
use App\Models\InmuebleCaptado;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use App\Services\CrmEntityResolver;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InmuebleCaptadoController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly CrmEntityResolver $resolver
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 25), 1);

        $items = InmuebleCaptado::query()
            ->with(['inmueble.cliente'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (InmuebleCaptado $captado) {
                return [
                    'id' => $captado->id,
                    'cliente' => $captado->inmueble->cliente->nombre ?? '—',
                    'inmueble' => $captado->inmueble->direccion ?? '—',
                    'estado' => $captado->estado,
                    'captacion_id' => $captado->captacion_id,
                    'fecha' => $captado->created_at,
                ];
            });

        return response()->json([
            'message' => 'Inmuebles captados recuperados.',
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $data = $request->validate([
            'cliente_nombre' => ['nullable', 'string', 'max:255'],
            'inmueble_nombre' => ['required', 'string', 'max:255'],
            'identificador' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string'],
            'proxima_accion' => ['nullable', 'string', 'max:255'],
            'fecha_proxima_accion' => ['nullable', 'string'],
        ]);

        $cliente = $this->resolver->resolveCliente(
            $data['cliente_nombre'] ?? 'Cliente sin nombre',
            null,
            null,
            $usuario->id
        );

        $inmueble = $this->resolver->resolveInmueble($data['inmueble_nombre'], $cliente, [
            'descripcion' => $data['notas'] ?? null,
            'notas' => $data['notas'] ?? null,
        ]);

        $captacion = Captacion::firstOrCreate([
            'inmueble_id' => $inmueble->id,
            'usuario_id' => $usuario->id,
        ], [
            'estado' => 'Activo',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'notas' => $data['notas'] ?? null,
        ]);

        $captado = InmuebleCaptado::create([
            'inmueble_id' => $inmueble->id,
            'captacion_id' => $captacion->id,
            'estado' => $data['proxima_accion'] ?? 'En seguimiento',
        ]);

        $this->resolver->registerHistorialAccion($cliente, $inmueble, $usuario->id, [
            'etapa' => 'Promocion',
            'accion' => $data['proxima_accion'] ?? null,
            'notas' => $data['notas'] ?? null,
            'fecha_proxima_accion' => $data['fecha_proxima_accion'] ?? null,
            'interesado_nombre' => $cliente->nombre,
        ]);

        return response()->json([
            'message' => 'Inmueble captado registrado.',
            'data' => [
                'id' => $captado->id,
                'captacion_id' => $captacion->id,
                'inmueble' => $inmueble->direccion,
            ],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $captado = InmuebleCaptado::with(['inmueble.cliente', 'captacion'])->find($id);

        if (! $captado) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de inmueble captado recuperado.',
            'data' => [
                'id' => $captado->id,
                'cliente' => $captado->inmueble->cliente->nombre ?? '—',
                'inmueble' => $captado->inmueble->direccion ?? '—',
                'estado' => $captado->estado,
                'captacion_id' => $captado->captacion_id,
                'notas' => $captado->captacion->notas ?? null,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $captado = InmuebleCaptado::find($id);

        if (! $captado) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        $data = $request->validate([
            'estado' => ['nullable', 'string', 'max:120'],
        ]);

        if (isset($data['estado'])) {
            $captado->estado = $data['estado'];
            $captado->save();
        }

        return response()->json([
            'message' => 'Inmueble captado actualizado.',
            'data' => $captado,
        ]);
    }

    public function historial(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $captado = InmuebleCaptado::with('inmueble')->find($id);

        if (! $captado) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        $captacion = Captacion::find($captado->captacion_id);
        if ($captacion && $captacion->usuario_id !== $usuario->id) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        $acciones = DB::table('historial_acciones as ha')
            ->select([
                'ha.id',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
            ])
            ->where('ha.inmueble_id', $captado->inmueble_id)
            ->where('ha.usuario_id', $usuario->id)
            ->orderByDesc('ha.fecha_accion')
            ->get();

        if ($acciones->isEmpty()) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Historial de inmueble captado recuperado.',
            'inmueble_captado_id' => (int) $id,
            'data' => [
                'inmueble' => $captado->inmueble->direccion ?? '—',
                'estado' => $captado->estado,
                'acciones' => $acciones,
            ],
        ]);
    }

    public function historialGlobal(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min($limit, 1000));

        $acciones = DB::table('historial_acciones as ha')
            ->join('inmuebles as i', 'i.id', '=', 'ha.inmueble_id')
            ->join('inmuebles_captados as ic', 'ic.inmueble_id', '=', 'ha.inmueble_id')
            ->leftJoin('captaciones as cap', 'cap.id', '=', 'ic.captacion_id')
            ->select([
                'ha.id',
                'i.direccion as inmueble',
                'ic.estado',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
                'cap.id as captacion_id',
            ])
            ->where('ha.usuario_id', $usuario->id)
            ->orderByDesc('ha.fecha_accion')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial global de inmuebles captados recuperado.',
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
