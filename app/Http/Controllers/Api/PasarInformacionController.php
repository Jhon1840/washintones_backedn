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

class PasarInformacionController extends Controller
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

        $items = DB::table('pasar_informacion_registros as pi')
            ->join('pasar_informacion_clientes as c', 'c.id', '=', 'pi.cliente_id')
            ->join('pasar_informacion_inmuebles as i', 'i.id', '=', 'pi.inmueble_id')
            ->select([
                'pi.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'pi.estado',
                'pi.comentarios',
                'pi.created_at',
            ])
            ->where('pi.usuario_id', $usuario->id)
            ->orderByDesc('pi.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'cliente' => $row->cliente ?? '—',
                    'inmueble' => $row->inmueble ?? '—',
                    'estado' => $row->estado,
                    'comentarios' => $row->comentarios,
                    'fecha' => $row->created_at,
                ];
            });

        return response()->json([
            'message' => 'Registros recuperados.',
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $data = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'inmueble_nombre' => ['nullable', 'string', 'max:255'],
            'interesado_nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'asesor_destino' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
            'proxima_accion' => ['nullable', 'string', 'max:255'],
            'fecha_contacto' => ['nullable', 'string'],
            'fecha_proxima_accion' => ['nullable', 'string'],
        ]);

        $cliente = $this->resolver->resolveCliente(
            'pasar_informacion_clientes',
            $data['cliente_nombre'],
            $data['telefono'] ?? null,
            null,
            $usuario->id
        );

        $inmueble = $this->resolver->resolveInmueble(
            'pasar_informacion_inmuebles',
            $cliente->id,
            $data['inmueble_nombre'] ?? ('Propiedad de ' . $cliente->nombre),
            ['descripcion' => $data['notas'] ?? null],
            $usuario->id
        );

        $interesado = $this->resolver->resolveInteresado(
            $data['interesado_nombre'],
            $data['telefono'] ?? null,
            null
        );

        $registroId = DB::table('pasar_informacion_registros')->insertGetId([
            'cliente_id' => $cliente->id,
            'inmueble_id' => $inmueble->id,
            'usuario_id' => $usuario->id,
            'estado' => $data['proxima_accion'] ?? 'En seguimiento',
            'comentarios' => $data['notas'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->resolver->registerHistorialAccion('pasar_informacion_historial_acciones', $cliente->id, $inmueble->id, $usuario->id, [
            'etapa' => 'Promocion',
            'accion' => $data['proxima_accion'] ?? null,
            'notas' => $data['notas'] ?? null,
            'fecha_accion' => $data['fecha_contacto'] ?? null,
            'fecha_proxima_accion' => $data['fecha_proxima_accion'] ?? null,
            'interesado_nombre' => $interesado->nombre,
            'interesado_telefono' => $interesado->telefono,
            'asesor_nombre' => $data['asesor_destino'] ?? null,
        ]);

        return response()->json([
            'message' => 'Registro creado correctamente.',
            'data' => [
                'id' => $registroId,
                'cliente' => $cliente->nombre,
                'inmueble' => $inmueble->direccion,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $registro = DB::table('pasar_informacion_registros as pi')
            ->join('pasar_informacion_clientes as c', 'c.id', '=', 'pi.cliente_id')
            ->join('pasar_informacion_inmuebles as i', 'i.id', '=', 'pi.inmueble_id')
            ->select([
                'pi.id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'pi.estado',
                'pi.comentarios',
            ])
            ->where('pi.usuario_id', $usuario->id)
            ->where('pi.id', $id)
            ->first();

        if (! $registro) {
            return response()->json([
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle recuperado.',
            'data' => [
                'id' => $registro->id,
                'cliente' => $registro->cliente ?? '—',
                'inmueble' => $registro->inmueble ?? '—',
                'estado' => $registro->estado,
                'comentarios' => $registro->comentarios,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $registro = DB::table('pasar_informacion_registros')
            ->where('usuario_id', $usuario->id)
            ->where('id', $id)
            ->first();

        if (! $registro) {
            return response()->json([
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $data = $request->validate([
            'estado' => ['nullable', 'string', 'max:120'],
            'comentarios' => ['nullable', 'string'],
        ]);

        $updates = [];
        if (array_key_exists('estado', $data)) {
            $updates['estado'] = $data['estado'];
        }
        if (array_key_exists('comentarios', $data)) {
            $updates['comentarios'] = $data['comentarios'];
        }
        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('pasar_informacion_registros')->where('id', $id)->update($updates);
        }

        $updated = DB::table('pasar_informacion_registros')->where('id', $id)->first();

        return response()->json([
            'message' => 'Registro actualizado.',
            'data' => $updated,
        ]);
    }

    public function historial(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $registro = DB::table('pasar_informacion_registros as pi')
            ->join('pasar_informacion_clientes as c', 'c.id', '=', 'pi.cliente_id')
            ->join('pasar_informacion_inmuebles as i', 'i.id', '=', 'pi.inmueble_id')
            ->select([
                'pi.id',
                'pi.inmueble_id',
                'pi.estado',
                'c.nombre as cliente',
                'i.direccion as inmueble',
            ])
            ->where('pi.usuario_id', $usuario->id)
            ->where('pi.id', $id)
            ->first();

        if (! $registro) {
            return response()->json([
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $acciones = DB::table('pasar_informacion_historial_acciones as ha')
            ->select([
                'ha.id',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
            ])
            ->where('ha.inmueble_id', $registro->inmueble_id)
            ->where('ha.usuario_id', $usuario->id)
            ->whereNull('ha.deleted_at')
            ->orderByDesc('ha.fecha_accion')
            ->get();

        return response()->json([
            'message' => 'Historial recuperado.',
            'pasar_informacion_id' => (int) $id,
            'data' => [
                'cliente' => $registro->cliente ?? '—',
                'inmueble' => $registro->inmueble ?? '—',
                'estado' => $registro->estado,
                'acciones' => $acciones,
            ],
        ]);
    }

    public function historialGlobal(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min($limit, 1000));

        $acciones = DB::table('pasar_informacion_historial_acciones as ha')
            ->join('pasar_informacion_registros as pi', 'pi.inmueble_id', '=', 'ha.inmueble_id')
            ->join('pasar_informacion_clientes as c', 'c.id', '=', 'pi.cliente_id')
            ->join('pasar_informacion_inmuebles as i', 'i.id', '=', 'pi.inmueble_id')
            ->select([
                'ha.id',
                'pi.id as pasar_informacion_id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
            ])
            ->where('ha.usuario_id', $usuario->id)
            ->whereNull('ha.deleted_at')
            ->orderByDesc('ha.fecha_accion')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial global recuperado.',
            'data' => $acciones,
        ]);
    }

    public function historialPapelera(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);

        $activeInmuebles = DB::table('pasar_informacion_historial_acciones')
            ->select('inmueble_id')
            ->where('usuario_id', $usuario->id)
            ->whereNull('deleted_at');

        $rows = DB::table('pasar_informacion_historial_acciones as ha')
            ->join('pasar_informacion_registros as pi', 'pi.inmueble_id', '=', 'ha.inmueble_id')
            ->join('pasar_informacion_clientes as c', 'c.id', '=', 'pi.cliente_id')
            ->join('pasar_informacion_inmuebles as i', 'i.id', '=', 'pi.inmueble_id')
            ->select([
                'ha.id',
                'ha.inmueble_id',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
                'pi.id as pasar_informacion_id',
                'pi.estado',
                'c.nombre as cliente',
                'i.direccion as inmueble',
            ])
            ->where('ha.usuario_id', $usuario->id)
            ->whereNotNull('ha.deleted_at')
            ->whereNotIn('ha.inmueble_id', $activeInmuebles)
            ->orderByDesc('ha.fecha_accion')
            ->orderByDesc('ha.id')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $registroId = (int) ($row->pasar_informacion_id ?? 0);
            if ($registroId < 1) {
                continue;
            }

            $entry = (array) $row;

            if (! array_key_exists($registroId, $groups)) {
                $groups[$registroId] = [
                    'pasar_informacion_id' => $registroId,
                    'total_acciones' => 1,
                    'latest' => $entry,
                ];
                continue;
            }

            $groups[$registroId]['total_acciones'] += 1;
            if ($this->isMoreRecentRow($row, $groups[$registroId]['latest'])) {
                $groups[$registroId]['latest'] = $entry;
            }
        }

        return response()->json([
            'message' => 'Papelera recuperada.',
            'data' => array_values($groups),
        ]);
    }

    public function softDeleteHistorialGrupo(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $registroId = (int) $request->input('pasar_informacion_id');

        if ($registroId < 1) {
            return response()->json([
                'message' => 'Registro inválido.',
            ], 422);
        }

        $registro = DB::table('pasar_informacion_registros as pi')
            ->join('pasar_informacion_clientes as c', 'c.id', '=', 'pi.cliente_id')
            ->select(['pi.id', 'pi.inmueble_id'])
            ->where('pi.usuario_id', $usuario->id)
            ->where('pi.id', $registroId)
            ->first();

        if (! $registro) {
            return response()->json([
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $updated = DB::table('pasar_informacion_historial_acciones')
            ->where('inmueble_id', $registro->inmueble_id)
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
        $registroId = (int) $request->input('pasar_informacion_id');

        if ($registroId < 1) {
            return response()->json([
                'message' => 'Registro inválido.',
            ], 422);
        }

        $registro = DB::table('pasar_informacion_registros as pi')
            ->join('pasar_informacion_clientes as c', 'c.id', '=', 'pi.cliente_id')
            ->select(['pi.id', 'pi.inmueble_id'])
            ->where('pi.usuario_id', $usuario->id)
            ->where('pi.id', $registroId)
            ->first();

        if (! $registro) {
            return response()->json([
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $updated = DB::table('pasar_informacion_historial_acciones')
            ->where('inmueble_id', $registro->inmueble_id)
            ->where('usuario_id', $usuario->id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        return response()->json([
            'message' => $updated ? 'Historial restaurado.' : 'Historial no encontrado.',
            'total' => $updated,
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
