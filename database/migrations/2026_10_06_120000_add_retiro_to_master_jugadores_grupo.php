<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_jugadores_grupo', function (Blueprint $table) {
            $table->boolean('retirado')->default(false)->after('posicion');
            // En la fila del suplente: jugador lesionado al que reemplaza
            $table->foreignId('reemplaza_a_id')->nullable()->after('retirado')
                ->constrained('jugadores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_jugadores_grupo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reemplaza_a_id');
            $table->dropColumn('retirado');
        });
    }
};
