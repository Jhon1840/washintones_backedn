<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Seed de usuarios del CRM con diferentes perfiles de ejemplo.
     */
    public function run(): void
    {
        $usuarios = [
            [
                'nombre' => 'Admin Demo',
                'email' => 'admin@gmail.com',
                'telefono' => '555-0000',
                'password' => '123456',
                'activo' => true,
                'es_admin' => true,
            ],
            [
                'nombre' => 'Carla Ramirez',
                'email' => 'carla@gmail.com',
                'telefono' => '555-0101',
                'password' => '123456',
                'activo' => true,
                'es_admin' => false,
            ],
            [
                'nombre' => 'Luis Ortega',
                'email' => 'luis.ortega@freddy-demo.test',
                'telefono' => '555-0202',
                'password' => 'password',
                'activo' => true,
                'es_admin' => false,
            ],
            [
                'nombre' => 'Monica Cabrera',
                'email' => 'monica.cabrera@freddy-demo.test',
                'telefono' => '555-0303',
                'password' => 'password',
                'activo' => true,
                'es_admin' => false,
            ],
            [
                'nombre' => 'Andres Pineda',
                'email' => 'andres.pineda@freddy-demo.test',
                'telefono' => '555-0404',
                'password' => 'password',
                'activo' => false,
                'es_admin' => false,
            ],
        ];

        foreach ($usuarios as $usuario) {
            Usuario::updateOrCreate(
                ['email' => $usuario['email']],
                [
                    'nombre' => $usuario['nombre'],
                    'telefono' => $usuario['telefono'],
                    'password' => Hash::make($usuario['password']),
                    'activo' => $usuario['activo'],
                    'es_admin' => $usuario['es_admin'],
                ]
            );
        }
    }
}
