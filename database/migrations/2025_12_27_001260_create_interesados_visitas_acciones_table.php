<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interesados_visitas_acciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('visita_id')->constrained('interesados_visitas');
            $table->foreignId('accion_id')->constrained('catalogo_acciones');
            $table->date('fecha_programada');
            $table->timestamp('fecha_realizada');
            $table->text('notas');
            $table->foreignId('asesor_id')->constrained('asesores');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interesados_visitas_acciones');
    }
};