<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->foreignId('jugador_id')->constrained('jugadores')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            // ronda en la que perdió: 1=Final(2do), 2=Semifinal(3-4), 4=QF(5-8), etc.
            $table->integer('ronda_eliminado');
            $table->integer('puntos')->default(0);
            $table->timestamps();

            $table->unique(['torneo_id', 'jugador_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
