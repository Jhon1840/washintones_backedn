<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $today = now()->toDateString();

        $accionesHoy = DB::table('historial_acciones')
            ->whereDate('fecha_accion', $today)
            ->where('usuario_id', $usuario->id)
            ->count();

        $accionesHoy += DB::table('colocacion_historial_acciones')
            ->whereDate('fecha_accion', $today)
            ->where('usuario_id', $usuario->id)
            ->count();

        $accionesHoy += DB::table('visitas_historial_acciones')
            ->whereDate('fecha_accion', $today)
            ->where('usuario_id', $usuario->id)
            ->count();

        $accionesHoy += DB::table('pasar_informacion_historial_acciones')
            ->whereDate('fecha_accion', $today)
            ->where('usuario_id', $usuario->id)
            ->count();

        $accionesHoy += DB::table('inmuebles_captados_historial_acciones')
            ->whereDate('fecha_accion', $today)
            ->where('usuario_id', $usuario->id)
            ->count();

        $visitasHoy = DB::table('visitas_registros as v')
            ->join('visitas_clientes as c', 'c.id', '=', 'v.cliente_id')
            ->where('c.usuario_id', $usuario->id)
            ->whereDate('v.fecha', $today)
            ->count();

        $tareasVencidas = DB::table('tareas as t')
            ->join('historial_acciones as ha', 'ha.id', '=', 't.historial_id')
            ->where('ha.usuario_id', $usuario->id)
            ->where('t.completado', false)
            ->whereDate('t.fecha', '<', $today)
            ->count();

        $captacionesActivas = DB::table('captaciones')
            ->where('usuario_id', $usuario->id)
            ->whereNull('fecha_fin')
            ->count();

        $cerradaId = DB::table('catalogo_estados_colocacion')
            ->whereRaw('LOWER(nombre) = ?', ['cerrada'])
            ->value('id');

        $colocacionesActivas = DB::table('colocacion_registros as col')
            ->join('colocacion_inmuebles as i', 'i.id', '=', 'col.inmueble_id')
            ->join('colocacion_clientes as c', 'c.id', '=', 'i.cliente_id')
            ->where('c.usuario_id', $usuario->id)
            ->when($cerradaId, fn ($query) => $query->where('col.estado_id', '!=', $cerradaId))
            ->count();

        return response()->json([
            'acciones_hoy' => $accionesHoy,
            'visitas_hoy' => $visitasHoy,
            'tareas_vencidas' => $tareasVencidas,
            'captaciones_activas' => $captacionesActivas,
            'colocaciones_activas' => $colocacionesActivas,
            'message' => 'Dashboard actualizado.',
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
