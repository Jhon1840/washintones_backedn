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

class ColocacionController extends Controller
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

        $colocaciones = DB::table('colocacion_registros as col')
            ->join('colocacion_inmuebles as i', 'i.id', '=', 'col.inmueble_id')
            ->join('colocacion_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->leftJoin('asesores as a', 'a.id', '=', 'col.asesor_id')
            ->select([
                'col.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'col.estado_id',
                'a.nombre as asesor',
                'col.notas',
                'col.created_at',
            ])
            ->where('c.usuario_id', $usuario->id)
            ->orderByDesc('col.created_at')
            ->limit($limit)
            ->get();

        $items = $colocaciones->map(function ($row) {
            return [
                'id' => $row->id,
                'cliente' => $row->cliente ?? '—',
                'inmueble' => $row->inmueble ?? '—',
                'estado_id' => $row->estado_id,
                'asesor' => $row->asesor ?? '—',
                'notas' => $row->notas,
                'created_at' => $row->created_at,
            ];
        });

        return response()->json([
            'message' => 'Colocaciones recuperadas.',
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $data = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:255'],
            'descripcion_busqueda' => ['nullable', 'string'],
            'operacion' => ['nullable', 'string', 'max:120'],
            'tipo_inmueble' => ['nullable', 'string', 'max:120'],
            'zona' => ['nullable', 'string', 'max:120'],
            'presupuesto' => ['nullable'],
            'moneda' => ['nullable', 'string', 'max:10'],
            'inmueble_sugerido' => ['nullable', 'string', 'max:255'],
            'asesor_nombre' => ['nullable', 'string', 'max:255'],
            'asesor_telefono' => ['nullable', 'string', 'max:50'],
            'notas' => ['nullable', 'string'],
            'proxima_accion' => ['nullable', 'string', 'max:255'],
            'fecha_proxima_accion' => ['nullable', 'string'],
        ]);

        $cliente = $this->resolver->resolveCliente(
            'colocacion_clientes',
            $data['cliente_nombre'],
            $data['telefono'] ?? null,
            $data['correo'] ?? null,
            $usuario->id
        );

        $direccion = $data['inmueble_sugerido'] ?? ('Oportunidad ' . $cliente->nombre);

        $inmueble = $this->resolver->resolveInmueble('colocacion_inmuebles', $cliente->id, $direccion, [
            'descripcion' => $data['descripcion_busqueda'] ?? null,
            'notas' => $data['notas'] ?? null,
            'valor_estimado' => $data['presupuesto'] ?? null,
            'operacion' => $data['operacion'] ?? null,
            'tipo' => $data['tipo_inmueble'] ?? null,
            'zona' => $data['zona'] ?? null,
            'moneda' => $data['moneda'] ?? null,
        ]);

        $operacionId = $this->resolver->catalogId('catalogo_operaciones', $data['operacion'] ?? null);
        $tipoId = $this->resolver->catalogId('catalogo_tipos_inmueble', $data['tipo_inmueble'] ?? null);
        $zonaId = $this->resolver->catalogId('catalogo_zonas', $data['zona'] ?? null);
        $monedaId = $this->resolver->catalogId('catalogo_monedas', $data['moneda'] ?? 'MXN', 'codigo');

        $busqueda = $this->resolver->ensureBusqueda(
            'colocacion_busquedas_clientes',
            'colocacion_busqueda_inmueble',
            $cliente->id,
            $data,
            $operacionId,
            $tipoId,
            $zonaId,
            $monedaId,
            $inmueble->id
        );

        $asesorNombre = $data['asesor_nombre'] ?? $usuario->nombre;
        $asesor = $this->resolver->resolveAsesor(
            $asesorNombre,
            $data['asesor_telefono'] ?? null,
            $data['correo'] ?? null
        );

        if (! $asesor) {
            $asesor = $this->resolver->resolveAsesor('Asesor ' . $cliente->nombre);
        }

        $asesorNombre = $asesor->nombre;

        $estadoId = $this->resolver->catalogId('catalogo_estados_colocacion', null);

        $colocacionId = DB::table('colocacion_registros')->insertGetId([
            'busqueda_id' => $busqueda->id,
            'inmueble_id' => $inmueble->id,
            'asesor_id' => $asesor->id,
            'estado_id' => $estadoId,
            'notas' => $data['notas'] ?? 'Colocación generada desde app móvil.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->resolver->registerHistorialAccion('colocacion_historial_acciones', $cliente->id, $inmueble->id, $usuario->id, [
            'etapa' => 'Promocion',
            'accion' => $data['proxima_accion'] ?? null,
            'notas' => $data['notas'] ?? null,
            'fecha_proxima_accion' => $data['fecha_proxima_accion'] ?? null,
            'interesado_nombre' => $data['cliente_nombre'],
            'interesado_telefono' => $data['telefono'] ?? null,
            'interesado_email' => $data['correo'] ?? null,
            'asesor_nombre' => $asesorNombre,
            'asesor_telefono' => $data['asesor_telefono'] ?? null,
        ]);

        return response()->json([
            'message' => 'Colocación registrada correctamente.',
            'data' => [
                'id' => $colocacionId,
                'cliente' => $cliente->nombre,
                'inmueble' => $inmueble->direccion,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $colocacion = DB::table('colocacion_registros as col')
            ->join('colocacion_inmuebles as i', 'i.id', '=', 'col.inmueble_id')
            ->join('colocacion_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->leftJoin('asesores as a', 'a.id', '=', 'col.asesor_id')
            ->select([
                'col.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'col.estado_id',
                'a.nombre as asesor',
                'col.notas',
            ])
            ->where('c.usuario_id', $usuario->id)
            ->where('col.id', $id)
            ->first();

        if (! $colocacion) {
            return response()->json([
                'message' => 'Colocación no encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de colocación recuperado.',
            'data' => [
                'id' => $colocacion->id,
                'cliente' => $colocacion->cliente ?? '—',
                'inmueble' => $colocacion->inmueble ?? '—',
                'estado_id' => $colocacion->estado_id,
                'asesor' => $colocacion->asesor ?? '—',
                'notas' => $colocacion->notas,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $colocacion = DB::table('colocacion_registros as col')
            ->join('colocacion_inmuebles as i', 'i.id', '=', 'col.inmueble_id')
            ->join('colocacion_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->where('c.usuario_id', $usuario->id)
            ->where('col.id', $id)
            ->select(['col.id'])
            ->first();

        if (! $colocacion) {
            return response()->json([
                'message' => 'Colocación no encontrada.',
            ], 404);
        }

        $data = $request->validate([
            'estado_id' => ['nullable', 'integer'],
            'notas' => ['nullable', 'string'],
        ]);

        $updates = [];
        if (isset($data['estado_id'])) {
            $updates['estado_id'] = (int) $data['estado_id'];
        }
        if (array_key_exists('notas', $data)) {
            $updates['notas'] = $data['notas'];
        }
        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('colocacion_registros')->where('id', $id)->update($updates);
        }

        $updated = DB::table('colocacion_registros')->where('id', $id)->first();

        return response()->json([
            'message' => 'Colocación actualizada.',
            'data' => $updated,
        ]);
    }

    public function historial(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $colocacion = DB::table('colocacion_registros as col')
            ->join('colocacion_inmuebles as i', 'i.id', '=', 'col.inmueble_id')
            ->join('colocacion_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->leftJoin('asesores as a', 'a.id', '=', 'col.asesor_id')
            ->select([
                'col.id',
                'col.inmueble_id',
                'col.estado_id',
                'col.notas',
                'i.direccion as inmueble',
                'a.nombre as asesor',
            ])
            ->where('c.usuario_id', $usuario->id)
            ->where('col.id', $id)
            ->first();

        if (! $colocacion) {
            return response()->json([
                'message' => 'Colocación no encontrada.',
            ], 404);
        }

        $tieneAcceso = DB::table('colocacion_historial_acciones')
            ->where('inmueble_id', $colocacion->inmueble_id)
            ->where('usuario_id', $usuario->id)
            ->exists();

        if (! $tieneAcceso) {
            return response()->json([
                'message' => 'Colocación no encontrada.',
            ], 404);
        }

        $acciones = $this->accionesBaseQuery($usuario->id)
            ->where('ha.inmueble_id', $colocacion->inmueble_id)
            ->orderByDesc('ha.fecha_accion')
            ->get();

        return response()->json([
            'message' => 'Historial de colocación recuperado.',
            'colocacion_id' => (int) $id,
            'data' => [
                'inmueble' => $colocacion->inmueble ?? '—',
                'asesor' => $colocacion->asesor ?? '—',
                'estado_id' => $colocacion->estado_id,
                'notas' => $colocacion->notas,
                'acciones' => $acciones,
            ],
        ]);
    }

    public function historialGlobal(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min($limit, 1000));

        $acciones = $this->accionesBaseQuery($usuario->id)
            ->join('colocacion_registros as col', 'col.inmueble_id', '=', 'ha.inmueble_id')
            ->join('asesores as ase', 'ase.id', '=', 'col.asesor_id')
            ->join('catalogo_estados_colocacion as est', 'est.id', '=', 'col.estado_id')
            ->join('colocacion_inmuebles as inm', 'inm.id', '=', 'ha.inmueble_id')
            ->select([
                'ha.id',
                'col.id as colocacion_id',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
                'ase.nombre as asesor',
                'est.nombre as estado',
                'inm.direccion as inmueble',
                'col.notas as colocacion_notas',
            ])
            ->orderByDesc('ha.fecha_accion')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial global de colocación recuperado.',
            'data' => $acciones,
        ]);
    }

    private function accionesBaseQuery(?int $usuarioId = null)
    {
        $query = DB::table('colocacion_historial_acciones as ha')
            ->select([
                'ha.id',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
                'ha.inmueble_id',
            ]);

        if ($usuarioId !== null) {
            $query->where('ha.usuario_id', $usuarioId);
        }

        return $query;
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
