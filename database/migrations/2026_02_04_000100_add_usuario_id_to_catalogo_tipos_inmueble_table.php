<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalogo_tipos_inmueble', function (Blueprint $table) {
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete()
                ->after('nombre');

            $table->index(
                ['usuario_id', 'nombre'],
                'catalogo_tipos_inmueble_usuario_nombre_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_tipos_inmueble', function (Blueprint $table) {
            $table->dropIndex('catalogo_tipos_inmueble_usuario_nombre_index');
            $table->dropConstrainedForeignId('usuario_id');
        });
    }
};
