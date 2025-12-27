<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inmuebles_fotos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->string('url');
            $table->string('descripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmuebles_fotos');
    }
};