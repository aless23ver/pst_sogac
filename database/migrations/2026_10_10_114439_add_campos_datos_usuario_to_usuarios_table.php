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
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('usu_direccion')->nullable()->after('usu_trayecto');
            $table->string('usu_lugar_nacimiento')->nullable()->after('usu_direccion');
            $table->date('usu_fecha_nacimiento')->nullable()->after('usu_lugar_nacimiento');
            $table->string('usu_lapso_academico_previo')->nullable()->after('usu_fecha_nacimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('usu_direccion');
            $table->dropColumn('usu_lugar_nacimiento');
            $table->dropColumn('usu_fecha_nacimiento');
            $table->dropColumn('usu_lapso_academico_previo');
        });
    }
};
