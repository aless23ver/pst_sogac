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
        Schema::create('documentaciones', function (Blueprint $table) {
            $table->increments('doc_id');
            $table->unsignedInteger('doc_sol_id');
            $table->string('doc_nombre_original_archivo', 255);
            $table->string('doc_tipo_documento', 50)->nullable();
            $table->string('doc_formato_archivo', 20)->nullable();
            $table->bigInteger('doc_tamano_bytes')->nullable();
            $table->text('doc_ruta_almacenamiento_url');
            $table->timestamp('doc_fecha_subida')->useCurrent();
            $table->string('doc_estado_validacion', 20)->nullable();

            $table->foreign('doc_sol_id', 'fk_doc_sol')->references('sol_id')->on('solicitudes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentaciones');
    }
};
