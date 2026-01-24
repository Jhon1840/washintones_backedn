<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('historial_acciones', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->index();
        });

        Schema::table('colocacion_historial_acciones', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->index();
        });

        Schema::table('visitas_historial_acciones', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->index();
        });

        Schema::table('pasar_informacion_historial_acciones', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->index();
        });

        Schema::table('inmuebles_captados_historial_acciones', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('historial_acciones', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        Schema::table('colocacion_historial_acciones', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        Schema::table('visitas_historial_acciones', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        Schema::table('pasar_informacion_historial_acciones', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });

        Schema::table('inmuebles_captados_historial_acciones', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
