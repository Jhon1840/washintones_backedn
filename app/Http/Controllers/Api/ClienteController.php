<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Usuario;
use App\Services\AuthTokenService;
use App\Services\CrmEntityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly CrmEntityResolver $resolver
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 50), 1);
        $usuario = $this->requireUsuario($request);

        $clientes = Cliente::query()
            ->select(['id', 'nombre', 'telefono', 'email', 'created_at'])
            ->where('usuario_id', $usuario->id)
            ->orderBy('nombre')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Clientes recuperados.',
            'data' => $clientes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $data = $this->validateData($request, null, $usuario->id);
        $data['usuario_id'] = $usuario->id;
        $cliente = Cliente::create($data);

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data' => $cliente,
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $cliente = Cliente::where('usuario_id', $usuario->id)->find($id);

        if (! $cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de cliente recuperado.',
            'data' => $cliente,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $cliente = Cliente::where('usuario_id', $usuario->id)->find($id);

        if (! $cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $data = $this->validateData($request, $cliente->id, $usuario->id);
        $cliente->fill($data)->save();

        return response()->json([
            'message' => 'Cliente actualizado.',
            'data' => $cliente,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $cliente = Cliente::where('usuario_id', $usuario->id)->find($id);

        if (! $cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $cliente->delete();

        return response()->json([
            'message' => 'Cliente eliminado.',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) ($request->query('term') ?? $request->query('q') ?? ''));
        $usuario = $this->requireUsuario($request);

        if ($term === '') {
            return response()->json([
                'message' => 'Proporciona un término de búsqueda.',
                'data' => [],
            ]);
        }

        $clientes = Cliente::query()
            ->select(['id', 'nombre', 'telefono', 'email'])
            ->where('usuario_id', $usuario->id)
            ->where(function ($query) use ($term) {
                $query->where('nombre', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%')
                    ->orWhere('telefono', 'like', '%' . $term . '%');
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get();

        return response()->json([
            'message' => 'Resultados de búsqueda.',
            'data' => $clientes,
        ]);
    }

    public function relations(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $cliente = Cliente::where('usuario_id', $usuario->id)->find($id);

        if (! $cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $inmuebles = DB::table('inmuebles as i')
            ->leftJoin('catalogo_tipos_inmueble as cti', 'cti.id', '=', 'i.tipo_id')
            ->leftJoin('catalogo_amc_estados as cae', 'cae.id', '=', 'i.amc_estado_id')
            ->select([
                'i.id',
                'i.direccion',
                'i.descripcion',
                'i.tipo_id',
                'i.amc_estado_id',
                'i.valor_estimado',
                'cti.nombre as tipo_nombre',
                'cae.nombre as estado_amc_nombre',
            ])
            ->where('i.cliente_id', $cliente->id)
            ->orderByDesc('i.updated_at')
            ->orderByDesc('i.id')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nombre' => $row->direccion ?? 'Inmueble sin nombre',
                    'direccion' => $row->direccion ?? '—',
                    'descripcion' => $row->descripcion,
                    'tipo_id' => $row->tipo_id !== null ? (int) $row->tipo_id : null,
                    'tipo' => $row->tipo_nombre,
                    'valor' => $row->valor_estimado,
                    'valor_estimado' => $row->valor_estimado,
                    'amc_estado_id' => $row->amc_estado_id !== null ? (int) $row->amc_estado_id : null,
                    'amc' => $row->estado_amc_nombre,
                    'estado_amc' => $row->estado_amc_nombre,
                ];
            });

        $contactos = DB::table('historial_acciones as ha')
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

    public function storeInmueble(Request $request, string $id): JsonResponse
    {
        $usuario = $this->requireUsuario($request);
        $cliente = Cliente::where('usuario_id', $usuario->id)->find($id);

        if (! $cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $data = $request->validate([
            'direccion' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
            'valor_estimado' => ['nullable'],
            'tipo' => ['nullable', 'string', 'max:120'],
            'zona' => ['nullable', 'string', 'max:120'],
            'operacion' => ['nullable', 'string', 'max:120'],
            'estado_amc' => ['nullable', 'string', 'max:120'],
            'moneda' => ['nullable', 'string', 'max:10'],
        ]);

        $inmueble = $this->resolver->resolveInmueble(
            $data['direccion'],
            $cliente,
            [
                'descripcion' => $data['descripcion'] ?? null,
                'notas' => $data['notas'] ?? null,
                'valor_estimado' => $data['valor_estimado'] ?? null,
                'tipo' => $data['tipo'] ?? null,
                'zona' => $data['zona'] ?? null,
                'operacion' => $data['operacion'] ?? null,
                'estado_amc' => $data['estado_amc'] ?? null,
                'moneda' => $data['moneda'] ?? null,
            ],
            $usuario->id
        );

        return response()->json([
            'message' => 'Inmueble creado correctamente.',
            'data' => [
                'id' => $inmueble->id,
                'direccion' => $inmueble->direccion,
                'descripcion' => $inmueble->descripcion,
            ],
        ], 201);
    }

    private function validateData(Request $request, ?int $clienteId = null, ?int $usuarioId = null): array
    {
        $emailRule = Rule::unique('clientes', 'email')->ignore($clienteId);
        if ($usuarioId !== null) {
            $emailRule = $emailRule->where(function ($query) use ($usuarioId) {
                return $query->where('usuario_id', $usuarioId);
            });
        }

        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                $emailRule,
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
