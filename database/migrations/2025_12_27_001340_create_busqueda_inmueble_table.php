<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('busqueda_inmueble', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('busqueda_id')->constrained('busquedas_clientes');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('busqueda_inmueble');
    }
};
