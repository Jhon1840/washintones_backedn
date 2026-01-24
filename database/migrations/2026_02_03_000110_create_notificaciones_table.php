<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('titulo');
            $table->text('cuerpo');
            $table->string('tipo')->nullable();
            $table->string('fuente')->nullable();
            $table->unsignedBigInteger('fuente_id')->nullable();
            $table->dateTime('fecha_programada')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
