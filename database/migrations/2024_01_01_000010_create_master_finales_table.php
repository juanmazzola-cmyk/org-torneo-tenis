<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_finales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->enum('tipo', ['semifinal_1', 'semifinal_2', 'final']);
            $table->foreignId('jugador1_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->foreignId('jugador2_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->string('resultado')->nullable();
            $table->foreignId('ganador_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->timestamps();
            $table->unique(['master_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_finales');
    }
};
