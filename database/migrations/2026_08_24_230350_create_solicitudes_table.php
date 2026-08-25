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
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->increments('sol_id');
            $table->unsignedInteger('sol_usu_id');
            $table->unsignedInteger('sol_tsi_id');
            $table->unsignedInteger('sol_lac_id');
            $table->unsignedInteger('sol_eso_id');
            $table->string('sol_id_seguimiento', 50)->unique();
            $table->text('sol_motivo_detallado')->nullable();
            $table->string('sol_prioridad', 20)->nullable();
            $table->timestamp('sol_fecha_creacion')->useCurrent();
            $table->timestamp('sol_fecha_ultima_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('sol_fecha_resolucion')->nullable();

            $table->foreign('sol_usu_id', 'fk_sol_usu')->references('usu_id')->on('usuarios');
            $table->foreign('sol_tsi_id', 'fk_sol_tsi')->references('tsi_id')->on('tipo_solicitudes');
            $table->foreign('sol_lac_id', 'fk_sol_lac')->references('lac_id')->on('lapso_academicos');
            $table->foreign('sol_eso_id', 'fk_sol_eso')->references('eso_id')->on('estado_solicitudes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
