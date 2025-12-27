<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inmuebles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('direccion');
            $table->text('descripcion');
            $table->foreignId('tipo_id')->constrained('catalogo_tipos_inmueble');
            $table->foreignId('zona_id')->constrained('catalogo_zonas');
            $table->foreignId('operacion_id')->constrained('catalogo_operaciones');
            $table->foreignId('amc_estado_id')->constrained('catalogo_amc_estados');
            $table->decimal('valor_estimado', 15, 2);
            $table->foreignId('moneda_id')->constrained('catalogo_monedas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};