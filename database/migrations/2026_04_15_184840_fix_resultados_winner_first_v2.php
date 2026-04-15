<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Segunda pasada del fix winner-first.
 *
 * Detecta de forma inteligente si un resultado ya está en formato ganador-primero:
 * para un partido donde ganador == jugador2, el resultado está INCORRECTO si el
 * primer jugador en el marcador ganó MENOS sets que el segundo (es decir, el primer
 * número pertenece al perdedor). Solo en ese caso se voltean los sets.
 *
 * Esto permite correr la migración de forma idempotente: datos ya corregidos no
 * se modifican dos veces.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['partidos', 'master_partidos', 'master_finales'] as $table) {
            if (!$this->tableExists($table)) continue;

            $rows = DB::table($table)
                ->whereNotNull('ganador_id')
                ->whereNotNull('resultado')
                ->whereRaw('ganador_id = jugador2_id')
                ->whereNotIn('resultado', ['Bye', 'W.O.'])
                ->get(['id', 'resultado']);

            foreach ($rows as $row) {
                if ($this->necesitaFlip($row->resultado)) {
                    DB::table($table)->where('id', $row->id)
                        ->update(['resultado' => $this->flipSets($row->resultado)]);
                }
            }
        }
    }

    public function down(): void
    {
        // Idempotente — no requiere reversión.
    }

    /**
     * Determina si el resultado está en formato "perdedor primero".
     * Para partidos donde j2 ganó el match, si en el marcador almacenado
     * el primer jugador (j1) ganó MENOS sets que el segundo, los datos están
     * en formato incorrecto y hay que voltearlos.
     */
    private function necesitaFlip(string $resultado): bool
    {
        $j1Sets = 0;
        $j2Sets = 0;
        foreach (explode(' ', trim($resultado)) as $set) {
            if (!preg_match('/^(\d+)-(\d+)$/', $set, $m)) continue;
            if ((int)$m[1] > (int)$m[2]) $j1Sets++;
            elseif ((int)$m[2] > (int)$m[1]) $j2Sets++;
        }
        // Si el segundo número ganó más sets, el primer número es del perdedor
        return $j2Sets > $j1Sets;
    }

    private function flipSets(string $resultado): string
    {
        return implode(' ', array_map(function (string $set) {
            if (preg_match('/^(\d+)-(\d+)$/', $set, $m)) {
                return "{$m[2]}-{$m[1]}";
            }
            return $set;
        }, explode(' ', trim($resultado))));
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
