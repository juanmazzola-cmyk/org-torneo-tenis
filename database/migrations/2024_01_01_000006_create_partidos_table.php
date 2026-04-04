<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('draws')->cascadeOnDelete();
            $table->integer('ronda');         // 1=Final, 2=Semifinal, 4=QF, etc.
            $table->integer('posicion');      // posición en el bracket (1-based)
            $table->foreignId('jugador1_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->foreignId('jugador2_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->foreignId('ganador_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->string('resultado', 100)->nullable(); // "6-4 6-3"
            $table->timestamps();

            $table->unique(['draw_id', 'ronda', 'posicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};
