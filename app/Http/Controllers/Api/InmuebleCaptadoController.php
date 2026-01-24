<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use App\Services\ModuleEntityResolver;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InmuebleCaptadoController extends Controller
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

        $items = DB::table('inmuebles_captados_registros as ic')
            ->join('inmuebles_captados_inmuebles as i', 'i.id', '=', 'ic.inmueble_id')
            ->join('inmuebles_captados_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->join('inmuebles_captados_captaciones as cap', 'cap.id', '=', 'ic.captacion_id')
            ->select([
                'ic.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'ic.estado',
                'ic.captacion_id',
                'ic.created_at',
            ])
            ->where('c.usuario_id', $usuario->id)
            ->orderByDesc('ic.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'cliente' => $row->cliente ?? '—',
                    'inmueble' => $row->inmueble ?? '—',
                    'estado' => $row->estado,
                    'captacion_id' => $row->captacion_id,
                    'fecha' => $row->created_at,
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
            'inmuebles_captados_clientes',
            $data['cliente_nombre'] ?? 'Cliente sin nombre',
            null,
            null,
            $usuario->id
        );

        $inmueble = $this->resolver->resolveInmueble('inmuebles_captados_inmuebles', $cliente->id, $data['inmueble_nombre'], [
            'descripcion' => $data['notas'] ?? null,
            'notas' => $data['notas'] ?? null,
        ]);

        $captacion = DB::table('inmuebles_captados_captaciones')
            ->where('cliente_id', $cliente->id)
            ->where('usuario_id', $usuario->id)
            ->first();

        if (! $captacion) {
            $captacionId = DB::table('inmuebles_captados_captaciones')->insertGetId([
                'cliente_id' => $cliente->id,
                'usuario_id' => $usuario->id,
                'estado' => 'Activo',
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => null,
                'notas' => $data['notas'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $captacion = DB::table('inmuebles_captados_captaciones')->where('id', $captacionId)->first();
        }

        $captadoId = DB::table('inmuebles_captados_registros')->insertGetId([
            'inmueble_id' => $inmueble->id,
            'captacion_id' => $captacion->id,
            'estado' => $data['proxima_accion'] ?? 'En seguimiento',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->resolver->registerHistorialAccion('inmuebles_captados_historial_acciones', $cliente->id, $inmueble->id, $usuario->id, [
            'etapa' => 'Promocion',
            'accion' => $data['proxima_accion'] ?? null,
            'notas' => $data['notas'] ?? null,
            'fecha_proxima_accion' => $data['fecha_proxima_accion'] ?? null,
            'interesado_nombre' => $cliente->nombre,
        ]);

        return response()->json([
            'message' => 'Inmueble captado registrado.',
            'data' => [
                'id' => $captadoId,
                'captacion_id' => $captacion->id,
                'inmueble' => $inmueble->direccion,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $captado = DB::table('inmuebles_captados_registros as ic')
            ->join('inmuebles_captados_inmuebles as i', 'i.id', '=', 'ic.inmueble_id')
            ->join('inmuebles_captados_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->join('inmuebles_captados_captaciones as cap', 'cap.id', '=', 'ic.captacion_id')
            ->select([
                'ic.id',
                'ic.estado',
                'ic.captacion_id',
                'i.direccion as inmueble',
                'c.nombre as cliente',
                'cap.notas as captacion_notas',
            ])
            ->where('c.usuario_id', $usuario->id)
            ->where('ic.id', $id)
            ->first();

        if (! $captado) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de inmueble captado recuperado.',
            'data' => [
                'id' => $captado->id,
                'cliente' => $captado->cliente ?? '—',
                'inmueble' => $captado->inmueble ?? '—',
                'estado' => $captado->estado,
                'captacion_id' => $captado->captacion_id,
                'notas' => $captado->captacion_notas,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $captado = DB::table('inmuebles_captados_registros as ic')
            ->join('inmuebles_captados_inmuebles as i', 'i.id', '=', 'ic.inmueble_id')
            ->join('inmuebles_captados_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->where('c.usuario_id', $usuario->id)
            ->where('ic.id', $id)
            ->select(['ic.id'])
            ->first();

        if (! $captado) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        $data = $request->validate([
            'estado' => ['nullable', 'string', 'max:120'],
        ]);

        if (isset($data['estado'])) {
            DB::table('inmuebles_captados_registros')
                ->where('id', $id)
                ->update([
                    'estado' => $data['estado'],
                    'updated_at' => now(),
                ]);
        }

        $updated = DB::table('inmuebles_captados_registros')->where('id', $id)->first();

        return response()->json([
            'message' => 'Inmueble captado actualizado.',
            'data' => $updated,
        ]);
    }

    public function historial(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $captado = DB::table('inmuebles_captados_registros as ic')
            ->join('inmuebles_captados_inmuebles as i', 'i.id', '=', 'ic.inmueble_id')
            ->join('inmuebles_captados_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->select([
                'ic.id',
                'ic.estado',
                'ic.inmueble_id',
                'i.direccion as inmueble',
                'c.usuario_id',
            ])
            ->where('ic.id', $id)
            ->first();

        if (! $captado || $captado->usuario_id !== $usuario->id) {
            return response()->json([
                'message' => 'Inmueble captado no encontrado.',
            ], 404);
        }

        $acciones = DB::table('inmuebles_captados_historial_acciones as ha')
            ->select([
                'ha.id',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
            ])
            ->where('ha.inmueble_id', $captado->inmueble_id)
            ->where('ha.usuario_id', $usuario->id)
            ->whereNull('ha.deleted_at')
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
                'inmueble' => $captado->inmueble ?? '—',
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

        $acciones = DB::table('inmuebles_captados_historial_acciones as ha')
            ->join('inmuebles_captados_inmuebles as i', 'i.id', '=', 'ha.inmueble_id')
            ->join('inmuebles_captados_registros as ic', 'ic.inmueble_id', '=', 'ha.inmueble_id')
            ->leftJoin('inmuebles_captados_captaciones as cap', 'cap.id', '=', 'ic.captacion_id')
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
            ->whereNull('ha.deleted_at')
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
