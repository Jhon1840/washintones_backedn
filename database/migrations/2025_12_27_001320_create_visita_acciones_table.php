<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visita_acciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('visita_id')->constrained('visitas');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->dateTime('fecha');
            $table->text('descripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visita_acciones');
    }
};
