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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('usu_id');
            $table->enum('usu_rol', ['estudiante', 'admin']);
            $table->unsignedInteger('usu_tdo_id');
            $table->string('usu_primer_nombre', 50);
            $table->string('usu_segundo_nombre', 50)->nullable();
            $table->string('usu_primer_apellido', 50);
            $table->string('usu_segundo_apellido', 50)->nullable();
            $table->string('usu_numero_documento', 20)->unique();
            $table->string('usu_correo_electronico', 100)->unique();
            $table->string('usu_numero_telefono', 20)->nullable();
            $table->string('usu_contrasena_hash', 255);
            $table->string('usu_estado_cuenta', 20);
            $table->timestamp('usu_fecha_registro')->useCurrent();
            $table->timestamp('usu_ultimo_acceso')->nullable();

            $table->foreign('usu_tdo_id', 'fk_usu_tdo')->references('tdo_id')->on('tipo_documentos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
