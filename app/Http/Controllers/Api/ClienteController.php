<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = max((int) $request->query('limit', 50), 1);

        $clientes = Cliente::query()
            ->select(['id', 'nombre', 'telefono', 'email', 'created_at'])
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
        $data = $this->validateData($request);
        $cliente = Cliente::create($data);

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data' => $cliente,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $cliente = Cliente::find($id);

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
        $cliente = Cliente::find($id);

        if (! $cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $data = $this->validateData($request, $cliente->id);
        $cliente->fill($data)->save();

        return response()->json([
            'message' => 'Cliente actualizado.',
            'data' => $cliente,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $cliente = Cliente::find($id);

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

        if ($term === '') {
            return response()->json([
                'message' => 'Proporciona un término de búsqueda.',
                'data' => [],
            ]);
        }

        $clientes = Cliente::query()
            ->select(['id', 'nombre', 'telefono', 'email'])
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

    private function validateData(Request $request, ?int $clienteId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clientes', 'email')->ignore($clienteId),
            ],
        ]);
    }
}
