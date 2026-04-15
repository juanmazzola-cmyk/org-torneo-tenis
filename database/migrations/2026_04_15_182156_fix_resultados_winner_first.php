<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige resultados almacenados donde el primer número es del perdedor.
 * Convención deseada: el primer número de cada set es siempre del ganador del partido.
 * Afecta a partidos donde ganador_id == jugador2_id (es decir, j2 ganó pero los
 * números estaban guardados como j1-j2, con el perdedor primero).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['partidos', 'master_partidos', 'master_finales'] as $table) {
            if (!$this->tableExists($table)) continue;

            DB::table($table)
                ->whereNotNull('ganador_id')
                ->whereNotNull('resultado')
                ->whereRaw('ganador_id = jugador2_id')
                ->whereNotIn('resultado', ['Bye', 'W.O.'])
                ->orderBy('id')
                ->each(function ($row) use ($table) {
                    $flipped = $this->flipSets($row->resultado);
                    if ($flipped !== $row->resultado) {
                        DB::table($table)->where('id', $row->id)->update(['resultado' => $flipped]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Volver a aplicar el flip revierte el cambio (la operación es su propio inverso).
        $this->up();
    }

    private function flipSets(string $resultado): string
    {
        return collect(explode(' ', trim($resultado)))
            ->map(function (string $set) {
                if (preg_match('/^(\d+)-(\d+)$/', $set, $m)) {
                    return "{$m[2]}-{$m[1]}";
                }
                return $set;
            })
            ->implode(' ');
    }

    private function tableExists(string $table): bool
    {
        try {
            DB::table($table)->limit(1)->get();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};
