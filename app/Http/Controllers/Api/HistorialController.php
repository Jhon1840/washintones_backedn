<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistorialController extends Controller
{
    private const TABLE_MAP = [
        'captacion' => 'historial_acciones',
        'captaciones' => 'historial_acciones',
        'historial_acciones' => 'historial_acciones',
        'colocacion' => 'colocacion_historial_acciones',
        'colocaciones' => 'colocacion_historial_acciones',
        'colocacion_historial_acciones' => 'colocacion_historial_acciones',
        'visita' => 'visitas_historial_acciones',
        'visitas' => 'visitas_historial_acciones',
        'visitas_historial_acciones' => 'visitas_historial_acciones',
        'pasar_informacion' => 'pasar_informacion_historial_acciones',
        'pasar-informacion' => 'pasar_informacion_historial_acciones',
        'pasar_informacion_historial_acciones' => 'pasar_informacion_historial_acciones',
        'inmuebles_captados' => 'inmuebles_captados_historial_acciones',
        'inmueble_captado' => 'inmuebles_captados_historial_acciones',
        'inmuebles_captados_historial_acciones' => 'inmuebles_captados_historial_acciones',
    ];

    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $limit = (int) $request->query('limit', 200);
        $limit = max(1, min($limit, 1000));

        $filters = $request->only([
            'entidad',
            'cliente_id',
            'inmueble_id',
            'fecha_inicio',
            'fecha_fin',
        ]);

        $query = DB::table('historial_acciones as ha')
            ->join('clientes as c', 'c.id', '=', 'ha.cliente_id')
            ->join('inmuebles as i', 'i.id', '=', 'ha.inmueble_id')
            ->join('catalogo_etapas as ce', 'ce.id', '=', 'ha.etapa_id')
            ->join('catalogo_acciones as ca', 'ca.id', '=', 'ha.accion_id')
            ->leftJoin('interesados as interes', 'interes.id', '=', 'ha.interesado_id')
            ->leftJoin('asesores as a', 'a.id', '=', 'ha.asesor_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'ha.usuario_id')
            ->leftJoin('captaciones as cap', 'cap.cliente_id', '=', 'ha.cliente_id')
            ->leftJoin('colocaciones as col', 'col.inmueble_id', '=', 'ha.inmueble_id')
            ->leftJoin('inmuebles_captados as ic', 'ic.inmueble_id', '=', 'ha.inmueble_id')
            ->leftJoin('pasar_informacion as pi', 'pi.inmueble_id', '=', 'ha.inmueble_id')
            ->leftJoin('visitas as v', 'v.inmueble_id', '=', 'ha.inmueble_id')
            ->select([
                'ha.id',
                'ha.cliente_id',
                'ha.inmueble_id',
                'ha.usuario_id',
                'c.nombre as cliente',
                'i.direccion as inmueble',
                'ce.nombre as etapa',
                'ca.nombre as accion',
                'ha.notas',
                'ha.fecha_accion',
                'ha.fecha_proxima_accion',
                'interes.nombre as interesado',
                'a.nombre as asesor',
                'u.nombre as usuario',
                'cap.id as captacion_id',
                'col.id as colocacion_id',
                'ic.id as inmueble_captado_id',
                'pi.id as pasar_informacion_id',
                'v.id as visita_id',
            ]);
        $query->whereNull('ha.deleted_at');

        if (! empty($filters['cliente_id'])) {
            $query->where('ha.cliente_id', (int) $filters['cliente_id']);
        }

        if (! empty($filters['inmueble_id'])) {
            $query->where('ha.inmueble_id', (int) $filters['inmueble_id']);
        }

        $query->where('ha.usuario_id', $usuario->id);

        if (! empty($filters['fecha_inicio'])) {
            $query->whereDate('ha.fecha_accion', '>=', $filters['fecha_inicio']);
        }

        if (! empty($filters['fecha_fin'])) {
            $query->whereDate('ha.fecha_accion', '<=', $filters['fecha_fin']);
        }

        if (! empty($filters['entidad'])) {
            $entidad = strtolower((string) $filters['entidad']);

            if (in_array($entidad, ['captacion', 'captaciones'], true)) {
                $query->whereNotNull('cap.id');
            } elseif (in_array($entidad, ['colocacion', 'colocaciones'], true)) {
                $query->whereNotNull('col.id');
            } elseif (in_array($entidad, ['inmueble_captado', 'inmuebles_captados'], true)) {
                $query->whereNotNull('ic.id');
            } elseif (in_array($entidad, ['pasar_informacion', 'pasar-informacion'], true)) {
                $query->whereNotNull('pi.id');
            } elseif (in_array($entidad, ['visita', 'visitas'], true)) {
                $query->whereNotNull('v.id');
            }
        }

        $timeline = $query
            ->orderByDesc('ha.fecha_accion')
            ->orderByDesc('ha.id')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Historial unificado recuperado.',
            'filtros' => array_merge($filters, ['usuario_id' => $usuario->id]),
            'data' => $timeline,
        ]);
    }

    public function softDelete(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $table = $this->resolveTable((string) $request->input('tabla', ''));
        $id = (int) $request->input('id');

        if (! $table || $id < 1) {
            return response()->json([
                'message' => 'Tabla o id invalido.',
            ], 422);
        }

        $updated = DB::table($table)
            ->where('id', $id)
            ->where('usuario_id', $usuario->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        return response()->json([
            'message' => $updated ? 'Historial eliminado.' : 'Historial no encontrado.',
        ]);
    }

    public function softDeleteAll(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $tables = $this->resolveTables($request->input('tabla'));
        if (! $tables) {
            return response()->json([
                'message' => 'Tabla invalida.',
            ], 422);
        }
        $total = 0;

        foreach ($tables as $table) {
            $total += DB::table($table)
                ->where('usuario_id', $usuario->id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        }

        return response()->json([
            'message' => 'Historial eliminado.',
            'total' => $total,
            'tablas' => $tables,
        ]);
    }

    public function restoreAll(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $tables = $this->resolveTables($request->input('tabla'));
        if (! $tables) {
            return response()->json([
                'message' => 'Tabla invalida.',
            ], 422);
        }
        $total = 0;

        foreach ($tables as $table) {
            $total += DB::table($table)
                ->where('usuario_id', $usuario->id)
                ->whereNotNull('deleted_at')
                ->update(['deleted_at' => null]);
        }

        return response()->json([
            'message' => 'Historial restaurado.',
            'total' => $total,
            'tablas' => $tables,
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

    private function resolveTable(string $table): ?string
    {
        $key = strtolower(trim($table));
        return self::TABLE_MAP[$key] ?? null;
    }

    private function resolveTables(mixed $table): array
    {
        if (is_string($table) && trim($table) !== '') {
            $resolved = $this->resolveTable($table);
            return $resolved ? [$resolved] : [];
        }

        return array_values(array_unique(self::TABLE_MAP));
    }
}
