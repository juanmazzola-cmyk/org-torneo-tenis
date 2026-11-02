<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            // No escribe puntos en rankings y se oculta de los filtros del Ranking
            $table->boolean('sin_ranking')->default(false)->after('nombre');
            // Cada "jugador" de la categoría es una pareja ("Pérez / Gómez")
            $table->boolean('es_dobles')->default(false)->after('sin_ranking');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn(['sin_ranking', 'es_dobles']);
        });
    }
};
