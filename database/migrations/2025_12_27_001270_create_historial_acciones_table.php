<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historial_acciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->foreignId('interesado_id')->constrained('interesados');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('asesor_id')->constrained('asesores');
            $table->foreignId('etapa_id')->constrained('catalogo_etapas');
            $table->foreignId('accion_id')->constrained('catalogo_acciones');
            $table->text('notas');
            $table->date('fecha_accion');
            $table->date('fecha_proxima_accion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_acciones');
    }
};