<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes_chat', function (Blueprint $table) {
            $table->id('mch_id');
            $table->foreignId('mch_id_hilo')->constrained('hilos_chat', 'hch_id');
            $table->unsignedInteger('mch_id_remitente');
            $table->foreign('mch_id_remitente')->references('usu_id')->on('usuarios');
            $table->text('mch_cuerpo')->nullable();
            $table->string('mch_ruta_imagen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_chat'); 
    }
};