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
        Schema::create('hilos_chat', function (Blueprint $table) {
            $table->id('hch_id'); // PK
            
            // FKs: Prefijo de la tabla actual + _id_ + referencia
            $table->foreignId('hch_id_usuario')->constrained('usuarios', 'usu_id');
            $table->foreignId('hch_id_admin')->nullable()->constrained('usuarios', 'usu_id');
            
            // Campos normales
            $table->string('hch_estado')->default('pendiente'); 
            $table->string('hch_etiqueta_tema')->nullable();
            $table->timestamp('hch_fecha_solicitud_cierre')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hilo_chats');
    }
};
