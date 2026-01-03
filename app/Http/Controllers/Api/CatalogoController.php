<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    public function tiposInmueble(): JsonResponse
    {
        return $this->catalogoSimple('catalogo_tipos_inmueble');
    }

    public function zonas(): JsonResponse
    {
        return $this->catalogoSimple('catalogo_zonas');
    }

    public function operaciones(): JsonResponse
    {
        return $this->catalogoSimple('catalogo_operaciones');
    }

    public function estadosAmc(): JsonResponse
    {
        return $this->catalogoSimple('catalogo_amc_estados');
    }

    public function acciones(): JsonResponse
    {
        $items = DB::table('catalogo_acciones as ca')
            ->join('catalogo_etapas as ce', 'ce.id', '=', 'ca.etapa_id')
            ->select([
                'ca.id',
                'ca.nombre',
                'ce.nombre as etapa',
            ])
            ->orderBy('ca.nombre')
            ->get();

        return response()->json([
            'message' => 'Catálogo acciones recuperado.',
            'data' => $items,
        ]);
    }

    public function monedas(): JsonResponse
    {
        return $this->catalogoSimple('catalogo_monedas', orderBy: 'codigo', extraColumns: ['codigo']);
    }

    public function asesores(Request $request): JsonResponse
    {
        $query = DB::table('asesores')->select([
            'id',
            'nombre',
            'telefono',
            'email',
            'activo',
        ]);

        if ($request->boolean('solo_activos', true)) {
            $query->where('activo', true);
        }

        return response()->json([
            'message' => 'Catálogo asesores recuperado.',
            'data' => $query->orderBy('nombre')->get(),
        ]);
    }

    private function catalogoSimple(string $table, string $orderBy = 'nombre', array $extraColumns = []): JsonResponse
    {
        $columns = array_unique(array_merge(['id', 'nombre'], $extraColumns));

        $items = DB::table($table)
            ->select($columns)
            ->orderBy($orderBy)
            ->get();

        return response()->json([
            'message' => 'Catálogo recuperado.',
            'data' => $items,
        ]);
    }
}
