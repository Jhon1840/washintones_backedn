<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('colocaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('busqueda_id')->constrained('busquedas_clientes');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->foreignId('asesor_id')->constrained('asesores');
            $table->foreignId('estado_id')->constrained('catalogo_estados_colocacion');
            $table->text('notas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colocaciones');
    }
};