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
        Schema::create('historial_estado_solicitudes', function (Blueprint $table) {
            $table->increments('hes_id');
            $table->unsignedInteger('hes_sol_id');
            $table->unsignedInteger('hes_usu_id_responsable');
            $table->unsignedInteger('hes_eso_id_anterior')->nullable();
            $table->unsignedInteger('hes_eso_id_nuevo');
            $table->text('hes_observaciones_comentarios')->nullable();
            $table->timestamp('hes_fecha_cambio')->useCurrent();

            $table->foreign('hes_sol_id', 'fk_hes_sol')->references('sol_id')->on('solicitudes')->onDelete('cascade');
            $table->foreign('hes_usu_id_responsable', 'fk_hes_usu')->references('usu_id')->on('usuarios');
            $table->foreign('hes_eso_id_anterior', 'fk_hes_eso_ant')->references('eso_id')->on('estado_solicitudes');
            $table->foreign('hes_eso_id_nuevo', 'fk_hes_eso_nue')->references('eso_id')->on('estado_solicitudes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_solicitudes');
    }
};
