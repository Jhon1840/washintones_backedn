<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_monedas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 10);
            $table->string('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_monedas');
    }
};