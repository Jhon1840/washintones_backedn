<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('historial_id')->constrained('historial_acciones');
            $table->text('descripcion');
            $table->date('fecha');
            $table->foreignId('tipo_id')->constrained('catalogo_tipos_tarea');
            $table->boolean('completado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};