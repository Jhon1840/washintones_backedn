<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CatalogoSeeder::class,
            UsuarioSeeder::class,
            PlanesSeeder::class,
            SuscripcionesSeeder::class,
            DemoInmobiliariaSeeder::class,
            FlujoCompletoSeeder::class,
            FlujoUsuarioSeeder::class,
            CarlaMenusSeeder::class,
            CarlaNotificacionTareaSeeder::class,
        ]);

        User::factory()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User']
        );
    }
}
