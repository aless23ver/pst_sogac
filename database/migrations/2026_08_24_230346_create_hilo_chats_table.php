<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hilos_chat', function (Blueprint $table) {
            $table->id('hch_id');
            $table->unsignedInteger('hch_id_usuario');
            $table->foreign('hch_id_usuario')->references('usu_id')->on('usuarios');
            $table->unsignedInteger('hch_id_admin')->nullable();
            $table->foreign('hch_id_admin')->references('usu_id')->on('usuarios');
            $table->string('hch_estado')->default('pendiente'); 
            $table->string('hch_etiqueta_tema')->nullable();
            $table->timestamp('hch_fecha_solicitud_cierre')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hilos_chat'); 
    }
};