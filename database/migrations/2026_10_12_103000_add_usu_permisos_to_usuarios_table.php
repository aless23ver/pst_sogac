<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->json('usu_permisos')->nullable()->default(null)->after('usu_rol');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('usu_permisos');
        });
    }
};