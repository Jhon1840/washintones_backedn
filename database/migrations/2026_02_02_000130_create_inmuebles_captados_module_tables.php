<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inmuebles_captados_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('nombre');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->timestamps();
        });

        Schema::create('inmuebles_captados_inmuebles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('inmuebles_captados_clientes');
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

        Schema::create('inmuebles_captados_captaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('inmuebles_captados_clientes');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('estado');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('inmuebles_captados_registros', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inmueble_id')->constrained('inmuebles_captados_inmuebles');
            $table->foreignId('captacion_id')->constrained('inmuebles_captados_captaciones');
            $table->string('estado');
            $table->timestamps();
        });

        Schema::create('inmuebles_captados_historial_acciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('inmuebles_captados_clientes');
            $table->foreignId('inmueble_id')->constrained('inmuebles_captados_inmuebles');
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
        Schema::dropIfExists('inmuebles_captados_historial_acciones');
        Schema::dropIfExists('inmuebles_captados_registros');
        Schema::dropIfExists('inmuebles_captados_captaciones');
        Schema::dropIfExists('inmuebles_captados_inmuebles');
        Schema::dropIfExists('inmuebles_captados_clientes');
    }
};
