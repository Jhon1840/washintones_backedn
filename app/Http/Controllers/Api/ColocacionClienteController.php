<?php

namespace App\Http\Controllers\Api;

use App\Models\Usuario;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ColocacionClienteController extends ModuleClienteControllerBase
{
    protected string $clientesTable = 'colocacion_clientes';
    protected string $inmueblesTable = 'colocacion_inmuebles';
    protected string $historialTable = 'colocacion_historial_acciones';

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

        $ultimosRegistros = DB::table('colocacion_registros')
            ->select('inmueble_id', DB::raw('MAX(id) as registro_id'))
            ->groupBy('inmueble_id');

        $inmuebles = DB::table('colocacion_inmuebles as ci')
            ->leftJoin('catalogo_tipos_inmueble as cti', 'cti.id', '=', 'ci.tipo_id')
            ->leftJoin('catalogo_operaciones as co', 'co.id', '=', 'ci.operacion_id')
            ->leftJoin('catalogo_zonas as cz', 'cz.id', '=', 'ci.zona_id')
            ->leftJoinSub($ultimosRegistros, 'ur', function ($join) {
                $join->on('ur.inmueble_id', '=', 'ci.id');
            })
            ->leftJoin('colocacion_registros as cr', 'cr.id', '=', 'ur.registro_id')
            ->leftJoin('asesores as a', 'a.id', '=', 'cr.asesor_id')
            ->select([
                'ci.id',
                'ci.direccion',
                'ci.descripcion',
                'ci.valor_estimado',
                'cti.nombre as tipo',
                'co.nombre as operacion',
                'cz.nombre as zona',
                'a.id as asesor_id',
                'a.nombre as asesor',
                'a.telefono as telefono_asesor',
                'a.email as asesor_email',
            ])
            ->where('ci.cliente_id', $cliente->id)
            ->orderByDesc('ci.updated_at')
            ->orderByDesc('ci.id')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nombre' => $row->direccion ?? 'Inmueble sin nombre',
                    'inmueble_nombre' => $row->direccion ?? 'Inmueble sin nombre',
                    'direccion' => $row->direccion ?? '—',
                    'direccion_inmueble' => $row->direccion ?? '—',
                    'descripcion' => $row->descripcion,
                    'descripcion_inmueble' => $row->descripcion,
                    'tipo' => $row->tipo,
                    'tipo_inmueble' => $row->tipo,
                    'valor' => $row->valor_estimado,
                    'precio' => $row->valor_estimado,
                    'operacion' => $row->operacion,
                    'zona' => $row->zona,
                    'asesor_id' => $row->asesor_id !== null ? (int) $row->asesor_id : null,
                    'asesor' => $row->asesor,
                    'telefono_asesor' => $row->telefono_asesor,
                    'asesor_telefono' => $row->telefono_asesor,
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
