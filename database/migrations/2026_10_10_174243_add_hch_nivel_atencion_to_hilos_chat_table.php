<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nivel de atención de cada chat de soporte.
     *
     * 1 = taquillero, 2 = analista, 3 = administrador. Un chat nace en el
     * nivel 1 y SUBE cuando el estudiante pide hablar con alguien más arriba
     * o cuando quien lo atiende no puede resolverlo: al subir vuelve a la
     * bandeja como pendiente, para que lo reclame un rol de ese nivel o
     * superior. Los niveles bajos ya no lo ven.
     */
    public function up(): void
    {
        Schema::table('hilos_chat', function (Blueprint $table) {
            $table->unsignedTinyInteger('hch_nivel_atencion')->default(1)->after('hch_estado');
        });
    }

    public function down(): void
    {
        Schema::table('hilos_chat', function (Blueprint $table) {
            $table->dropColumn('hch_nivel_atencion');
        });
    }
};
