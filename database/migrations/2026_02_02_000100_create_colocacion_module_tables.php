<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('colocacion_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('nombre');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->timestamps();
        });

        Schema::create('colocacion_inmuebles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('colocacion_clientes');
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

        Schema::create('colocacion_busquedas_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('colocacion_clientes');
            $table->text('descripcion');
            $table->foreignId('operacion_id')->constrained('catalogo_operaciones');
            $table->foreignId('tipo_inmueble_id')->constrained('catalogo_tipos_inmueble');
            $table->foreignId('zona_id')->constrained('catalogo_zonas');
            $table->decimal('presupuesto', 15, 2);
            $table->foreignId('moneda_id')->constrained('catalogo_monedas');
            $table->timestamps();
        });

        Schema::create('colocacion_busqueda_inmueble', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('busqueda_id')->constrained('colocacion_busquedas_clientes');
            $table->foreignId('inmueble_id')->constrained('colocacion_inmuebles');
            $table->timestamps();
        });

        Schema::create('colocacion_registros', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('busqueda_id')->constrained('colocacion_busquedas_clientes');
            $table->foreignId('inmueble_id')->constrained('colocacion_inmuebles');
            $table->foreignId('asesor_id')->constrained('asesores');
            $table->foreignId('estado_id')->constrained('catalogo_estados_colocacion');
            $table->text('notas');
            $table->timestamps();
        });

        Schema::create('colocacion_historial_acciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('colocacion_clientes');
            $table->foreignId('inmueble_id')->constrained('colocacion_inmuebles');
            $table->foreignId('interesado_id')->constrained('interesados');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('asesor_id')->constrained('asesores');
            $table->foreignId('etapa_id')->constrained('catalogo_etapas');
            $table->foreignId('accion_id')->constrained('catalogo_acciones');
            $table->text('notas');
            $table->date('fecha_accion');
            $table->date('fecha_proxima_accion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colocacion_historial_acciones');
        Schema::dropIfExists('colocacion_registros');
        Schema::dropIfExists('colocacion_busqueda_inmueble');
        Schema::dropIfExists('colocacion_busquedas_clientes');
        Schema::dropIfExists('colocacion_inmuebles');
        Schema::dropIfExists('colocacion_clientes');
    }
};
