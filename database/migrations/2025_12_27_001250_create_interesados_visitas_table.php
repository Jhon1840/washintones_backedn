<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interesados_visitas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->foreignId('cliente_propietario_id')->constrained('clientes');
            $table->foreignId('interesado_id')->constrained('interesados');
            $table->foreignId('asesor_id')->constrained('asesores');
            $table->date('fecha_contacto');
            $table->text('notas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interesados_visitas');
    }
};