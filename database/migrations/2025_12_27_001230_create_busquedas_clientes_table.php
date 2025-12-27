<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('busquedas_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->text('descripcion');
            $table->foreignId('operacion_id')->constrained('catalogo_operaciones');
            $table->foreignId('tipo_inmueble_id')->constrained('catalogo_tipos_inmueble');
            $table->foreignId('zona_id')->constrained('catalogo_zonas');
            $table->decimal('presupuesto', 15, 2);
            $table->foreignId('moneda_id')->constrained('catalogo_monedas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('busquedas_clientes');
    }
};