<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalogo_acciones', function (Blueprint $table) {
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete()
                ->after('etapa_id');

            $table->index(
                ['usuario_id', 'etapa_id', 'nombre'],
                'catalogo_acciones_usuario_etapa_nombre_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_acciones', function (Blueprint $table) {
            $table->dropIndex('catalogo_acciones_usuario_etapa_nombre_index');
            $table->dropConstrainedForeignId('usuario_id');
        });
    }
};
