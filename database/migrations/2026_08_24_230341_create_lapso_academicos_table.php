<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lapso_academicos', function (Blueprint $table) {
            $table->increments('lac_id');
            $table->string('lac_id_lapso', 20)->unique();
            $table->date('lac_fecha_inicio');
            $table->date('lac_fecha_cierre');
            $table->string('lac_estado_lapso', 20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lapso_academicos');
    }
};
