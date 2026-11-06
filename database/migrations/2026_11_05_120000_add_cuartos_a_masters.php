<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dobles con 8 clasificados (cuartos de final).
 *  - masters.clasifican: 4 (semis, el formato de siempre) u 8 (cuartos). Los Masters existentes quedan en 4.
 *  - master_finales.tipo: suma cuartos_1..cuartos_4 (los valores existentes no cambian).
 * En producción se aplica a mano en phpMyAdmin ANTES del deploy (ver CLAUDE.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->unsignedTinyInteger('clasifican')->default(4)->after('estado');
        });

        Schema::table('master_finales', function (Blueprint $table) {
            $table->enum('tipo', [
                'cuartos_1', 'cuartos_2', 'cuartos_3', 'cuartos_4',
                'semifinal_1', 'semifinal_2', 'final',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('master_finales', function (Blueprint $table) {
            $table->enum('tipo', ['semifinal_1', 'semifinal_2', 'final'])->change();
        });

        Schema::table('masters', function (Blueprint $table) {
            $table->dropColumn('clasifican');
        });
    }
};
