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
        Schema::create('tipo_solicitud_requisitos', function (Blueprint $table) {
            $table->unsignedInteger('tsr_tsi_id');
            $table->unsignedInteger('tsr_req_id');
            $table->boolean('tsr_es_obligatorio')->default(true);

            $table->primary(['tsr_tsi_id', 'tsr_req_id']);
            $table->foreign('tsr_tsi_id', 'fk_tsr_tsi')->references('tsi_id')->on('tipo_solicitudes')->onDelete('cascade');
            $table->foreign('tsr_req_id', 'fk_tsr_req')->references('req_id')->on('requisitos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_solicitud_requisitos');
    }
};
