<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->enum('tamano', ['8', '16', '32', '64'])->default('8');
            $table->timestamps();

            $table->unique(['torneo_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
