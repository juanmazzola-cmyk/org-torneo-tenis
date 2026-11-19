<?php

namespace App\Support;

class Resultado
{
    /**
     * Super tie-break (match tie-break a 10 que reemplaza al 3er set): se carga como "Set 3"
     * y no queda marcado, así que se detecta por los números: 3er set donde alguno llega a 10+.
     * Con tie-break en 6-6 un set normal no pasa de 7.
     *
     * Para desempates (ATP/LTA) cuenta como 1 set y como 1 game a 0 para el ganador,
     * sin importar los puntos: 6-3 3-6 10-8 → sets 2-1, games 10-9.
     */
    public static function esSuperTiebreak(int $nroSet, int $a, int $b): bool
    {
        return $nroSet === 3 && max($a, $b) >= 10;
    }
}
