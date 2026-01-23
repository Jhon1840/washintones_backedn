<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('captaciones', function (Blueprint $table) {
            if (Schema::hasColumn('captaciones', 'inmueble_id')) {
                $table->dropConstrainedForeignId('inmueble_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('captaciones', function (Blueprint $table) {
            if (! Schema::hasColumn('captaciones', 'inmueble_id')) {
                $table->foreignId('inmueble_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('inmuebles');
            }
        });
    }
};
