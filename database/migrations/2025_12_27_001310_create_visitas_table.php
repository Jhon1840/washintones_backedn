<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->dateTime('fecha');
            $table->string('estado');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};
