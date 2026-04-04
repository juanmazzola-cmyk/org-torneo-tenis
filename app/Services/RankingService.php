<?php

namespace App\Services;

use App\Models\Partido;
use App\Models\Ranking;
use App\Models\Categoria;
use App\Models\Torneo;

class RankingService
{
    /**
     * Calcula el ranking con los 5 criterios de desempate.
     * Retorna array de bloques por categoría con jugadores ordenados.
     */
    public static function calcular(?int $filtroCategoria = null, ?int $filtroTorneo = null): array
    {
        $categorias = Categoria::orderBy('nombre')
            ->when($filtroCategoria, fn($q) => $q->where('id', $filtroCategoria))
            ->get();

        if ($categorias->isEmpty()) return [];

        $categoriaIds = $categorias->pluck('id')->all();

        // Todos los rankings relevantes
        $todosRankings = Ranking::with(['jugador', 'torneo'])
            ->whereIn('categoria_id', $categoriaIds)
            ->when($filtroTorneo, fn($q) => $q->where('torneo_id', $filtroTorneo))
            ->get();

        $result = [];

        foreach ($categorias as $categoria) {
            $rankings = $todosRankings->where('categoria_id', $categoria->id);
            if ($rankings->isEmpty()) continue;

            // Torneos únicos que aparecen en el ranking de esta categoría
            $torneos = $rankings->pluck('torneo')->unique('id')
                ->sortBy('fecha_inicio')->values();

            // Agrupar por jugador y calcular todos los criterios
            $jugadores = $rankings->groupBy('jugador_id')
                ->map(function ($filas) use ($torneos, $categoria) {
                    $jugadorId = (int) $filas->first()->jugador_id;
                    $jugador   = $filas->first()->jugador;

                    // Criterio 1: puntos totales y por torneo
                    $puntosPorTorneo = [];
                    foreach ($torneos as $torneo) {
                        $puntosPorTorneo[$torneo->id] = (int) $filas
                            ->where('torneo_id', $torneo->id)
                            ->sum('puntos');
                    }
                    $totalPuntos    = array_sum($puntosPorTorneo);

                    // Criterio 2: cantidad de torneos jugados
                    $torneosJugados = $filas->pluck('torneo_id')->unique()->count();

                    // Criterio 3: mejor puntaje en un único torneo
                    $mejorTorneo    = $puntosPorTorneo ? max($puntosPorTorneo) : 0;

                    // Criterios 4 y 5: query directa de partidos del jugador en esta categoría
                    $setsGanados = 0; $setsPerdidos = 0;
                    $gamesGanados = 0; $gamesPerdidos = 0;

                    $misPartidos = Partido::whereHas('draw', fn($q) => $q->where('categoria_id', $categoria->id))
                        ->where(fn($q) => $q->where('jugador1_id', $jugadorId)->orWhere('jugador2_id', $jugadorId))
                        ->whereNotNull('ganador_id')
                        ->whereNotNull('resultado')
                        ->where('resultado', '!=', 'Bye')
                        ->get();

                    foreach ($misPartidos as $partido) {
                        $esGanador = (int)$partido->ganador_id === $jugadorId;
                        $sg = self::parsearResultado($partido->resultado, $esGanador);
                        $setsGanados   += $sg['sets_ganados'];
                        $setsPerdidos  += $sg['sets_perdidos'];
                        $gamesGanados  += $sg['games_ganados'];
                        $gamesPerdidos += $sg['games_perdidos'];
                    }

                    return [
                        'jugador'         => $jugador,
                        'puntos'          => $puntosPorTorneo,
                        'total'           => $totalPuntos,
                        'torneos_jugados' => $torneosJugados,
                        'mejor_torneo'    => $mejorTorneo,
                        'sets_diff'       => $setsGanados - $setsPerdidos,
                        'games_diff'      => $gamesGanados - $gamesPerdidos,
                    ];
                })
                ->sort(function ($a, $b) {
                    foreach (['total', 'torneos_jugados', 'mejor_torneo', 'sets_diff', 'games_diff'] as $key) {
                        if ($b[$key] !== $a[$key]) {
                            return $b[$key] <=> $a[$key]; // descendente
                        }
                    }
                    return 0;
                })
                ->values();

            // Mostrar estrellas solo si al menos un torneo de esta categoría tiene campeón
            $hayTorneoFinalizado = $rankings->contains('ronda_eliminado', 0);

            $result[] = [
                'categoria'           => $categoria,
                'torneos'             => $torneos,
                'jugadores'           => $jugadores,
                'hay_torneo_finalizado' => $hayTorneoFinalizado,
            ];
        }

        return $result;
    }

    /**
     * Parsea un resultado tipo "6-4 6-3" o "1-6 6-2 6-3".
     * El primer número de cada set es siempre del ganador del partido.
     */
    private static function parsearResultado(string $resultado, bool $esGanador): array
    {
        $setsGanados = 0; $setsPerdidos = 0;
        $gamesGanados = 0; $gamesPerdidos = 0;

        foreach (explode(' ', trim($resultado)) as $set) {
            $set = strtolower($set);
            if ($set === 'retiro') {
                // Retiro = 6-0 a favor del ganador del partido
                if ($esGanador) {
                    $gamesGanados  += 6;
                    $setsGanados++;
                } else {
                    $gamesPerdidos += 6;
                    $setsPerdidos++;
                }
                continue;
            }
            if (!preg_match('/^(\d+)-(\d+)$/', $set, $m)) continue;

            $ganadorGames  = (int) $m[1]; // primer número = match winner
            $perdedorGames = (int) $m[2]; // segundo número = match loser

            if ($esGanador) {
                $gamesGanados  += $ganadorGames;
                $gamesPerdidos += $perdedorGames;
                if ($ganadorGames > $perdedorGames) $setsGanados++;
                else                                $setsPerdidos++;
            } else {
                $gamesGanados  += $perdedorGames;
                $gamesPerdidos += $ganadorGames;
                if ($perdedorGames > $ganadorGames) $setsGanados++;
                else                                $setsPerdidos++;
            }
        }

        return [
            'sets_ganados'   => $setsGanados,
            'sets_perdidos'  => $setsPerdidos,
            'games_ganados'  => $gamesGanados,
            'games_perdidos' => $gamesPerdidos,
        ];
    }
}
