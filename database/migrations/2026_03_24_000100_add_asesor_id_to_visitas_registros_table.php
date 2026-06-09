<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visitas_registros', function (Blueprint $table) {
            $table->foreignId('asesor_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('asesores');
        });
    }

    public function down(): void
    {
        Schema::table('visitas_registros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asesor_id');
        });
    }
};
