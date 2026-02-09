<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogoController extends Controller
{
    public function __construct(private readonly AuthTokenService $tokens)
    {
    }

    public function tiposInmueble(Request $request): JsonResponse
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        $query = DB::table('catalogo_tipos_inmueble')->select(['id', 'nombre']);
        $hasUsuarioColumn = Schema::hasColumn('catalogo_tipos_inmueble', 'usuario_id');

        if ($usuario && $hasUsuarioColumn) {
            $query->where(function ($builder) use ($usuario) {
                $builder->whereNull('usuario_id')
                    ->orWhere('usuario_id', $usuario->id);
            });
        } elseif ($hasUsuarioColumn) {
            $query->whereNull('usuario_id');
        }

        return response()->json([
            'message' => 'Catálogo recuperado.',
            'data' => $query->orderBy('nombre')->get(),
        ]);
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
        $usuario = $this->tokens->resolveUserFromRequest(request());

        $items = DB::table('catalogo_acciones as ca')
            ->join('catalogo_etapas as ce', 'ce.id', '=', 'ca.etapa_id')
            ->select([
                'ca.id',
                'ca.nombre',
                'ce.nombre as etapa',
            ])
            ->when($usuario, function ($query) use ($usuario) {
                $query->where(function ($builder) use ($usuario) {
                    $builder->whereNull('ca.usuario_id')
                        ->orWhere('ca.usuario_id', $usuario->id);
                });
            }, function ($query) {
                $query->whereNull('ca.usuario_id');
            })
            ->orderBy('ca.nombre')
            ->get();

        return response()->json([
            'message' => 'Catálogo acciones recuperado.',
            'data' => $items,
        ]);
    }

    public function accionesCaptacion(): JsonResponse
    {
        return $this->accionesPorEtapa(['Captacion', 'Captación'], 'Captación');
    }

    public function accionesColocacion(): JsonResponse
    {
        return $this->accionesPorEtapa(['Colocacion', 'Colocación'], 'Colocación');
    }

    public function accionesVisitas(): JsonResponse
    {
        return $this->accionesPorEtapa(['Visita', 'Visitas'], 'Visitas');
    }

    public function accionesPasarInformacion(): JsonResponse
    {
        return $this->accionesPorEtapa(
            [
                'Pasar Informacion',
                'Pasar Información',
                'Pasar informacion',
                'Pasar información',
                'Pasar-informacion',
                'Pasar-información',
                'PasarInformacion',
                'PasarInformación',
                'Pasar info',
            ],
            'Pasar información',
        );
    }

    public function accionesInmueblesCaptados(): JsonResponse
    {
        return $this->accionesPorEtapa(
            [
                'Inmueble Captado',
                'Inmuebles Captados',
                'Inmueble captado',
                'Inmuebles captados',
            ],
            'Inmuebles captados',
        );
    }

    public function monedas(): JsonResponse
    {
        return $this->catalogoSimple('catalogo_monedas', orderBy: 'codigo', extraColumns: ['codigo']);
    }

    public function storeAccionCaptacion(Request $request): JsonResponse
    {
        return $this->storeAccionForEtapa($request, ['Captación', 'Captacion'], 'Captación');
    }

    public function storeAccionColocacion(Request $request): JsonResponse
    {
        return $this->storeAccionForEtapa($request, ['Colocación', 'Colocacion'], 'Colocación');
    }

    public function storeAccionVisitas(Request $request): JsonResponse
    {
        return $this->storeAccionForEtapa($request, ['Visita', 'Visitas'], 'Visita');
    }

    public function storeAccionPasarInformacion(Request $request): JsonResponse
    {
        return $this->storeAccionForEtapa(
            $request,
            [
                'Pasar Información',
                'Pasar Informacion',
                'Pasar-informacion',
                'Pasar-información',
                'PasarInformacion',
                'PasarInformación',
                'Pasar info',
            ],
            'Pasar Información',
        );
    }

    public function storeAccionInmueblesCaptados(Request $request): JsonResponse
    {
        return $this->storeAccionForEtapa(
            $request,
            [
                'Inmuebles Captados',
                'Inmueble Captado',
                'Inmuebles captados',
                'Inmueble captado',
            ],
            'Inmuebles Captados',
        );
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

    private function accionesPorEtapa(array $etapas, string $label): JsonResponse
    {
        $usuario = $this->tokens->resolveUserFromRequest(request());
        $normalized = array_map(static fn ($value) => strtolower($value), $etapas);

        $items = DB::table('catalogo_acciones as ca')
            ->join('catalogo_etapas as ce', 'ce.id', '=', 'ca.etapa_id')
            ->select([
                'ca.id',
                'ca.nombre',
                'ce.nombre as etapa',
            ])
            ->whereIn(DB::raw('LOWER(ce.nombre)'), $normalized)
            ->when($usuario, function ($query) use ($usuario) {
                $query->where(function ($builder) use ($usuario) {
                    $builder->whereNull('ca.usuario_id')
                        ->orWhere('ca.usuario_id', $usuario->id);
                });
            }, function ($query) {
                $query->whereNull('ca.usuario_id');
            })
            ->orderBy('ca.nombre')
            ->get();

        return response()->json([
            'message' => "Catálogo acciones {$label} recuperado.",
            'data' => $items,
        ]);
    }

    private function storeAccionForEtapa(Request $request, array $etapas, string $label): JsonResponse
    {
        $usuario = $this->tokens->resolveUserFromRequest($request);

        if (! $usuario) {
            return response()->json([
                'message' => 'Token invalido o expirado.',
            ], 401);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $normalizedEtapas = array_map(static fn ($value) => strtolower($value), $etapas);

        $etapaId = DB::table('catalogo_etapas')
            ->whereIn(DB::raw('LOWER(nombre)'), $normalizedEtapas)
            ->value('id');

        if (! $etapaId) {
            $etapaId = DB::table('catalogo_etapas')->insertGetId([
                'nombre' => $label,
            ]);
        }

        $existing = DB::table('catalogo_acciones')
            ->where('etapa_id', $etapaId)
            ->where('usuario_id', $usuario->id)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($data['nombre'], 'UTF-8')])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Acción ya existe para este usuario.',
                'data' => $existing,
            ]);
        }

        $id = DB::table('catalogo_acciones')->insertGetId([
            'etapa_id' => $etapaId,
            'usuario_id' => $usuario->id,
            'nombre' => $data['nombre'],
        ]);

        return response()->json([
            'message' => 'Acción guardada.',
            'data' => [
                'id' => $id,
                'nombre' => $data['nombre'],
                'etapa_id' => $etapaId,
            ],
        ], 201);
    }
}
