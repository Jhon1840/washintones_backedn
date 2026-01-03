<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('captaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('estado');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captaciones');
    }
};
