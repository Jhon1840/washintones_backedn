<?php

namespace App\Http\Controllers\Api;

use App\Models\Usuario;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitaClienteController extends ModuleClienteControllerBase
{
    protected string $clientesTable = 'visitas_clientes';
    protected string $inmueblesTable = 'visitas_inmuebles';
    protected string $historialTable = 'visitas_historial_acciones';

    public function relations(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $cliente = DB::table($this->clientesTable)
            ->where('usuario_id', $usuario->id)
            ->where('id', $id)
            ->first();

        if (! $cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $ultimasVisitas = DB::table('visitas_registros')
            ->select('inmueble_id', DB::raw('MAX(id) as visita_id'))
            ->where('cliente_id', $cliente->id)
            ->groupBy('inmueble_id');

        $ultimosAsesores = DB::table('visitas_historial_acciones')
            ->select('inmueble_id', DB::raw('MAX(id) as historial_id'))
            ->where('cliente_id', $cliente->id)
            ->whereNull('deleted_at')
            ->groupBy('inmueble_id');

        $inmuebles = DB::table('visitas_inmuebles as vi')
            ->leftJoinSub($ultimasVisitas, 'uv', function ($join) {
                $join->on('uv.inmueble_id', '=', 'vi.id');
            })
            ->leftJoin('visitas_registros as vr', 'vr.id', '=', 'uv.visita_id')
            ->leftJoinSub($ultimosAsesores, 'ua', function ($join) {
                $join->on('ua.inmueble_id', '=', 'vi.id');
            })
            ->leftJoin('visitas_historial_acciones as vha', 'vha.id', '=', 'ua.historial_id')
            ->leftJoin('asesores as a', function ($join) {
                $join->on('a.id', '=', DB::raw('COALESCE(vr.asesor_id, vha.asesor_id)'));
            })
            ->select([
                'vi.id',
                'vi.direccion',
                'vi.descripcion',
                'a.id as asesor_id',
                'a.nombre as asesor',
                'a.telefono as asesor_telefono',
                'a.email as asesor_email',
            ])
            ->where('vi.cliente_id', $cliente->id)
            ->orderByDesc('vi.updated_at')
            ->orderByDesc('vi.id')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nombre' => $row->direccion ?? 'Inmueble sin nombre',
                    'direccion' => $row->direccion ?? '—',
                    'descripcion' => $row->descripcion,
                    'asesor_id' => $row->asesor_id !== null ? (int) $row->asesor_id : null,
                    'asesor' => $row->asesor,
                    'asesor_telefono' => $row->asesor_telefono,
                    'asesor_email' => $row->asesor_email,
                ];
            });

        $contactos = DB::table($this->historialTable . ' as ha')
            ->join('interesados as i', 'i.id', '=', 'ha.interesado_id')
            ->select([
                'i.id',
                'i.nombre',
                'i.telefono',
                'i.email',
                DB::raw('MAX(ha.fecha_accion) as ultima_accion'),
            ])
            ->where('ha.cliente_id', $cliente->id)
            ->whereNull('ha.deleted_at')
            ->groupBy('i.id', 'i.nombre', 'i.telefono', 'i.email')
            ->orderByDesc(DB::raw('MAX(ha.fecha_accion)'))
            ->limit(50)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nombre' => $row->nombre ?? 'Contacto sin nombre',
                    'telefono' => $row->telefono,
                    'email' => $row->email,
                    'ultima_accion' => $row->ultima_accion,
                ];
            });

        return response()->json([
            'message' => 'Relaciones del cliente recuperadas.',
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
            ],
            'data' => [
                'inmuebles' => $inmuebles,
                'contactos' => $contactos,
            ],
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
