<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained()->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'en_curso', 'finalizado'])->default('pendiente');
            $table->timestamps();
            $table->unique(['torneo_id', 'categoria_id']);
        });

        Schema::create('master_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero'); // 1 o 2
            $table->string('nombre');              // "Zona 1", "Zona 2"
            $table->timestamps();
        });

        Schema::create('master_jugadores_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('master_grupos')->cascadeOnDelete();
            $table->foreignId('jugador_id')->constrained('jugadores')->cascadeOnDelete();
            $table->unsignedTinyInteger('posicion'); // 1-4
            $table->timestamps();
            $table->unique(['grupo_id', 'jugador_id']);
            $table->unique(['grupo_id', 'posicion']);
        });

        Schema::create('master_partidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('master_grupos')->cascadeOnDelete();
            $table->unsignedTinyInteger('jornada'); // 1, 2, 3
            $table->unsignedTinyInteger('orden');   // 1 o 2
            $table->foreignId('jugador1_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->foreignId('jugador2_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->string('resultado')->nullable();
            $table->foreignId('ganador_id')->nullable()->constrained('jugadores')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_partidos');
        Schema::dropIfExists('master_jugadores_grupo');
        Schema::dropIfExists('master_grupos');
        Schema::dropIfExists('masters');
    }
};
