<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        $usuarios = Usuario::query()
            ->select(['id', 'nombre', 'email', 'telefono', 'activo', 'es_admin'])
            ->with(['suscripciones' => function ($query) {
                $query->orderByDesc('fecha_inicio')->orderByDesc('id');
            }])
            ->orderBy('nombre')
            ->get()
            ->map(function (Usuario $usuario) {
                $suscripcion = $usuario->suscripciones->first();
                $hoy = now()->toDateString();
                $suscripcionVencida = $suscripcion?->fecha_fin !== null
                    && $suscripcion->fecha_fin->toDateString() < $hoy;
                if ($suscripcion && $suscripcionVencida && strtolower((string) $suscripcion->estado) !== 'inactiva') {
                    $suscripcion->estado = 'inactiva';
                    $suscripcion->save();
                }

                return [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                    'telefono' => $usuario->telefono,
                    'activo' => (bool) $usuario->activo,
                    'es_admin' => (bool) $usuario->es_admin,
                    'suscripcion_id' => $suscripcion?->id,
                    'suscripcion_estado' => $suscripcion?->estado,
                    'fecha_inicio' => $suscripcion?->fecha_inicio?->toDateString(),
                    'fecha_fin' => $suscripcion?->fecha_fin?->toDateString(),
                    'suscripcion_vencida' => $suscripcionVencida,
                    'suscripcion_mensaje' => $suscripcionVencida
                        ? 'La suscripcion esta vencida.'
                        : null,
                ];
            });

        return response()->json([
            'message' => 'Usuarios recuperados.',
            'data' => $usuarios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $usuario = Usuario::create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? '',
            'password' => Hash::make($data['password']),
            'activo' => $data['activo'] ?? true,
            'es_admin' => $this->resolveAdminFlag($data),
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $usuario->only(['id', 'nombre', 'email', 'telefono', 'activo', 'es_admin']),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $usuario = Usuario::find($id);

        if (! $usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalle de usuario recuperado.',
            'data' => $usuario->only(['id', 'nombre', 'email', 'telefono', 'activo', 'es_admin']),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = Usuario::find($id);

        if (! $usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        $data = $this->validateData($request, $usuario->id, updating: true);
        $usuario->fill([
            'nombre' => $data['nombre'] ?? $usuario->nombre,
            'email' => $data['email'] ?? $usuario->email,
            'telefono' => $data['telefono'] ?? $usuario->telefono,
            'activo' => $data['activo'] ?? $usuario->activo,
            'es_admin' => array_key_exists('es_admin', $data)
                ? (bool) $data['es_admin']
                : $usuario->es_admin,
        ]);

        if (! empty($data['rol_id'])) {
            $usuario->es_admin = $this->resolveAdminFlag($data) ?: $usuario->es_admin;
        }

        if (! empty($data['password'])) {
            $usuario->password = Hash::make($data['password']);
        }

        $usuario->save();

        return response()->json([
            'message' => 'Usuario actualizado.',
            'data' => $usuario->only(['id', 'nombre', 'email', 'telefono', 'activo', 'es_admin']),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $usuario = Usuario::find($id);

        if (! $usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        $usuario->activo = false;
        $usuario->save();

        return response()->json([
            'message' => 'Usuario inactivado.',
        ]);
    }

    private function validateData(Request $request, ?int $usuarioId = null, bool $updating = false): array
    {
        $passwordRule = $updating ? ['nullable', 'string', 'min:6'] : ['required', 'string', 'min:6'];

        return $request->validate([
            'nombre' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => [
                $updating ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('usuarios', 'email')->ignore($usuarioId),
            ],
            'telefono' => ['nullable', 'string', 'max:50'],
            'password' => $passwordRule,
            'activo' => ['nullable', 'boolean'],
            'es_admin' => ['nullable', 'boolean'],
            'rol_id' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function resolveAdminFlag(array $data): bool
    {
        if (array_key_exists('es_admin', $data)) {
            return (bool) $data['es_admin'];
        }

        $rol = strtolower(trim((string) ($data['rol_id'] ?? '')));

        return in_array($rol, ['admin', 'administrator', 'premium'], true);
    }
}
