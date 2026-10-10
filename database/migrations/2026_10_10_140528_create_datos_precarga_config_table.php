<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración de los datos que el estudiante carga una vez y que se
     * precargan en los trámites.
     *
     * El administrador decide, campo por campo: si está habilitado para la
     * precarga, si el estudiante está obligado a llenarlo y cada cuántos
     * meses se considera vigente. Es la "versión de los datos" que controla
     * el admin y que el estudiante ve reflejada en su formulario.
     */
    public function up(): void
    {
        Schema::create('datos_precarga_config', function (Blueprint $table) {
            $table->increments('dpc_id');
            // Columna real de la tabla usuarios que guarda este dato.
            $table->string('dpc_campo')->unique();
            // Nombre con el que se le muestra al estudiante.
            $table->string('dpc_etiqueta');
            $table->boolean('dpc_obligatorio')->default(false);
            $table->boolean('dpc_activo')->default(true);
            // Meses que duran vigentes los datos; null = nunca vencen.
            $table->unsignedTinyInteger('dpc_vigencia_meses')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_precarga_config');
    }
};
