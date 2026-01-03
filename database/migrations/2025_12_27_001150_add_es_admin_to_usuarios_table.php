<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('usuarios', 'es_admin')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->boolean('es_admin')->default(false)->after('activo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'es_admin')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('es_admin');
            });
        }
    }
};
