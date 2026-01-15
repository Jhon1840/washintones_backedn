<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('usuarios', 'foto_url')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('foto_url')->nullable()->after('telefono');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'foto_url')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('foto_url');
            });
        }
    }
};

