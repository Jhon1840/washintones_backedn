<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('captaciones', function (Blueprint $table) {
            if (! Schema::hasColumn('captaciones', 'cliente_id')) {
                $table->foreignId('cliente_id')
                    ->nullable()
                    ->after('inmueble_id')
                    ->constrained('clientes');
            }
        });

        if (Schema::hasColumn('captaciones', 'cliente_id')) {
            DB::table('captaciones as cap')
                ->join('inmuebles as i', 'i.id', '=', 'cap.inmueble_id')
                ->whereNull('cap.cliente_id')
                ->update(['cap.cliente_id' => DB::raw('i.cliente_id')]);
        }
    }

    public function down(): void
    {
        Schema::table('captaciones', function (Blueprint $table) {
            if (Schema::hasColumn('captaciones', 'cliente_id')) {
                $table->dropConstrainedForeignId('cliente_id');
            }
        });
    }
};
