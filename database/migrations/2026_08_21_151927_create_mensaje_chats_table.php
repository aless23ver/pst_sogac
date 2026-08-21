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
        Schema::create('mensajes_chat', function (Blueprint $table) {
            $table->id('mch_id'); // PK
            
            // FKs: Prefijo de la tabla actual + _id_ + referencia
            $table->foreignId('mch_id_hilo')->constrained('hilos_chat', 'hch_id');
            $table->foreignId('mch_id_remitente')->constrained('usuarios', 'usu_id');
            
            // Campos normales
            $table->text('mch_cuerpo')->nullable();
            $table->string('mch_ruta_imagen')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensaje_chats');
    }
};
