<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inmuebles_captados', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('inmueble_id')->constrained('inmuebles');
            $table->foreignId('captacion_id')->constrained('captaciones');
            $table->string('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmuebles_captados');
    }
};
