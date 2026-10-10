<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deja constancia de cuándo actualizó el estudiante por última vez sus
     * datos de precarga (dirección, fecha de nacimiento, lapso previo...).
     *
     * El panel administrativo la usa para saber si los datos que el estudiante
     * adjunta a sus trámites siguen vigentes o ya están desactualizados.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->timestamp('usu_datos_ultima_actualizacion')->nullable()->after('usu_lapso_academico_previo');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('usu_datos_ultima_actualizacion');
        });
    }
};
