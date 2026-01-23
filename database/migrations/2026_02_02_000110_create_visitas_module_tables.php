<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visitas_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('nombre');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->timestamps();
        });

        Schema::create('visitas_inmuebles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('visitas_clientes');
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

        Schema::create('visitas_registros', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inmueble_id')->constrained('visitas_inmuebles');
            $table->foreignId('cliente_id')->constrained('visitas_clientes');
            $table->dateTime('fecha');
            $table->string('estado');
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('visitas_acciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('visita_id')->constrained('visitas_registros');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->dateTime('fecha');
            $table->text('descripcion');
            $table->timestamps();
        });

        Schema::create('visitas_historial_acciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cliente_id')->constrained('visitas_clientes');
            $table->foreignId('inmueble_id')->constrained('visitas_inmuebles');
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
        Schema::dropIfExists('visitas_historial_acciones');
        Schema::dropIfExists('visitas_acciones');
        Schema::dropIfExists('visitas_registros');
        Schema::dropIfExists('visitas_inmuebles');
        Schema::dropIfExists('visitas_clientes');
    }
};
