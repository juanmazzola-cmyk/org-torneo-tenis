<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->foreignId('jugador_id')->constrained('jugadores')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('observacion', 500)->nullable();
            $table->timestamps();

            // Un jugador puede estar en 2 categorías distintas en el mismo torneo
            $table->unique(['torneo_id', 'jugador_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
