<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Master extends Model
{
    // clasifican: 4 (semis) u 8 (cuartos, solo dobles sin ranking con 3 o 4 zonas)
    protected $fillable = ['torneo_id', 'categoria_id', 'estado', 'clasifican'];

    public function clasificanOcho(): bool
    {
        return (int) $this->clasifican === 8;
    }

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(MasterGrupo::class)->orderBy('numero');
    }

    /** El Master normal usa 2 zonas; el de dobles, de 2 a 4 */
    public function crearGrupos(int $cantidad = 2): void
    {
        $this->grupos()->delete();
        $this->grupos()->createMany(
            collect(range(1, $cantidad))->map(fn($n) => ['numero' => $n, 'nombre' => "Zona {$n}"])->all()
        );
    }

    /**
     * Clasificación de un Master de dobles (2 a 4 zonas de 3 o 4 parejas; siempre clasifican 4).
     *  - 2 zonas: 1° y 2° de cada una (semis como el Master: 1°Z1-2°Z2, 1°Z2-2°Z1).
     *  - 3 zonas: los 3 ganadores + el mejor segundo por promedios (ver MasterGrupo::promedios()).
     *    El mejor segundo juega con el mejor ganador de otra zona; los otros dos ganadores entre sí.
     *  - 4 zonas: el 1° de cada una (semis Z1-Z2 y Z3-Z4).
     * Los retirados nunca clasifican. Con empate total entre los mejores segundos se devuelven
     * en 'empatados' y no se arman semis, salvo que $mejorSegundoElegido sea uno de ellos.
     * Con 8 clasificados (cuartos) ver clasificacionCuartos().
     */
    public function clasificacionDobles(?int $mejorSegundoElegido = null, array $tercerosElegidos = []): array
    {
        if ($this->clasificanOcho()) return $this->clasificacionCuartos($tercerosElegidos);

        $grupos = $this->grupos()->get();
        $res = [
            'completo'      => false,
            'marcados'      => [],   // ids con ✓ en la tabla de su zona
            'segundos'      => [],   // comparación de segundos (solo con 3 zonas)
            'mejor_segundo' => null,
            'empatados'     => [],
            'semis'         => null, // [[j1, j2], [j1, j2]]
        ];
        if ($grupos->count() < 2 || $grupos->count() > 4) return $res;

        $res['completo'] = $grupos->every(fn($g) => $g->estaCompleto());

        $primeros = [];
        $segundos = [];
        $promedios = [];
        foreach ($grupos as $g) {
            $pos = array_values(array_filter($g->calcularPosiciones(), fn($s) => !$s['retirado']));
            $primeros[$g->numero] = $pos[0]['jugador'] ?? null;
            $segundos[$g->numero] = $pos[1]['jugador'] ?? null;
            $promedios[$g->numero] = $g->promedios();
        }

        foreach ($grupos as $g) {
            if ($primeros[$g->numero]) $res['marcados'][] = $primeros[$g->numero]->id;
            if ($grupos->count() === 2 && $segundos[$g->numero]) $res['marcados'][] = $segundos[$g->numero]->id;
        }

        if ($grupos->count() === 3) {
            foreach ($grupos as $g) {
                $j = $segundos[$g->numero];
                if (!$j) continue;
                $res['segundos'][] = ['jugador' => $j, 'zona' => $g->numero, 'nombre_zona' => $g->nombre]
                    + $promedios[$g->numero][$j->id];
            }
            usort($res['segundos'], [MasterGrupo::class, 'compararPromedios']);

            if ($res['completo'] && count($res['segundos']) === 3) {
                $mejor = $res['segundos'][0];
                $empatados = array_values(array_filter($res['segundos'],
                    fn($s) => MasterGrupo::compararPromedios($s, $mejor) === 0));

                if (count($empatados) > 1) {
                    $ids = array_map(fn($s) => (int) $s['jugador']->id, $empatados);
                    if (in_array((int) $mejorSegundoElegido, $ids, true)) {
                        $mejor = collect($empatados)->first(fn($s) => (int) $s['jugador']->id === (int) $mejorSegundoElegido);
                    } else {
                        $res['empatados'] = $ids;
                        $mejor = null;
                    }
                }

                if ($mejor) {
                    $res['mejor_segundo'] = $mejor['jugador']->id;
                    $res['marcados'][]    = $mejor['jugador']->id;
                    foreach ($res['segundos'] as &$s) $s['mejor'] = $s['jugador']->id === $mejor['jugador']->id;
                    unset($s);
                }
            }
        }

        if (!$res['completo']) return $res;

        $id = fn($j) => $j?->id;

        if ($grupos->count() === 2) {
            $semis = [[$id($primeros[1]), $id($segundos[2])], [$id($primeros[2]), $id($segundos[1])]];
        } elseif ($grupos->count() === 4) {
            $semis = [[$id($primeros[1]), $id($primeros[2])], [$id($primeros[3]), $id($primeros[4])]];
        } else {
            if (!$res['mejor_segundo']) return $res;
            $zonaSegundo = collect($res['segundos'])->firstWhere('mejor', true)['zona'];

            // Ganadores ordenados por el mismo promedio; ante empate total, por número de zona
            $ganadores = [];
            foreach ($grupos as $g) {
                $j = $primeros[$g->numero];
                if ($j) $ganadores[] = ['jugador' => $j, 'zona' => $g->numero] + $promedios[$g->numero][$j->id];
            }
            if (count($ganadores) !== 3) return $res;
            usort($ganadores, fn($a, $b) => MasterGrupo::compararPromedios($a, $b) ?: $a['zona'] <=> $b['zona']);

            $rival = collect($ganadores)->first(fn($w) => $w['zona'] !== $zonaSegundo);
            $otros = collect($ganadores)->reject(fn($w) => $w['jugador']->id === $rival['jugador']->id)
                ->sortBy('zona')->values();

            $semis = [
                [$rival['jugador']->id, $res['mejor_segundo']],
                [$otros[0]['jugador']->id, $otros[1]['jugador']->id],
            ];
        }

        if (collect($semis)->flatten()->contains(null)) return $res;
        $res['semis'] = $semis;
        return $res;
    }

    /**
     * Dobles con 8 clasificados (cuartos de final). Solo con 3 o 4 zonas, de 3 o 4 parejas cada una.
     *  - 4 zonas: 1° y 2° de cada una. CF1 1°Z1-2°Z2, CF2 1°Z3-2°Z4, CF3 1°Z2-2°Z1, CF4 1°Z4-2°Z3.
     *  - 3 zonas: 1° y 2° de cada una + los 2 mejores terceros por promedios (mismo criterio que el
     *    mejor segundo). Con A y B las zonas que aportan tercero (A < B) y C la otra:
     *    CF1 1°A-3°B, CF2 1°C-2°B, CF3 1°B-3°A, CF4 2°A-2°C.
     * Así nadie cruza a su propia zona en cuartos y el 1° y el 2° de cada zona solo se pueden
     * volver a ver en la final. Semis: ganador CF1 vs CF2 y ganador CF3 vs CF4.
     * Empate total en el corte de terceros: se devuelven en 'empate_terceros' (con 'cupos_terceros'
     * = cuántos de ellos pasan) y no se arman cuartos, salvo que $tercerosElegidos los defina.
     */
    private function clasificacionCuartos(array $tercerosElegidos): array
    {
        $grupos = $this->grupos()->get();
        $res = [
            'completo'        => false,
            'marcados'        => [],   // ids con ✓ en la tabla de su zona
            'segundos'        => [],   // (formato de 4 clasificados; acá siempre vacío)
            'mejor_segundo'   => null,
            'empatados'       => [],
            'semis'           => null,
            'terceros'        => [],   // comparación de terceros (solo con 3 zonas)
            'empate_terceros' => [],
            'cupos_terceros'  => 0,
            'cuartos'         => null, // [[j1, j2] x 4] en orden CF1..CF4
        ];
        if (!in_array($grupos->count(), [3, 4], true)) return $res;

        $res['completo'] = $grupos->every(fn($g) => $g->estaCompleto());

        $pos = []; // numero de zona => [1 => jugador, 2 => jugador, 3 => jugador]
        foreach ($grupos as $g) {
            $ok = array_values(array_filter($g->calcularPosiciones(), fn($s) => !$s['retirado']));
            $pos[$g->numero] = [1 => $ok[0]['jugador'] ?? null, 2 => $ok[1]['jugador'] ?? null, 3 => $ok[2]['jugador'] ?? null];
            foreach ([1, 2] as $k) {
                if ($pos[$g->numero][$k]) $res['marcados'][] = $pos[$g->numero][$k]->id;
            }
        }

        $zonasConTercero = [];
        if ($grupos->count() === 3) {
            foreach ($grupos as $g) {
                $j = $pos[$g->numero][3];
                if (!$j) continue;
                $res['terceros'][] = ['jugador' => $j, 'zona' => $g->numero, 'nombre_zona' => $g->nombre]
                    + $g->promedios()[$j->id];
            }
            usort($res['terceros'], [MasterGrupo::class, 'compararPromedios']);

            if ($res['completo'] && count($res['terceros']) === 3) {
                $t = $res['terceros'];
                // El corte está entre el 2° y el 3°: los mejores que el 2° pasan seguro; los
                // empatados con el 2° se reparten los lugares que quedan.
                $seguros   = array_values(array_filter($t, fn($s) => MasterGrupo::compararPromedios($s, $t[1]) < 0));
                $empatados = array_values(array_filter($t, fn($s) => MasterGrupo::compararPromedios($s, $t[1]) === 0));
                $cupos     = 2 - count($seguros);
                $clasifican = null;

                if (count($empatados) > $cupos) {
                    $ids      = array_map(fn($s) => (int) $s['jugador']->id, $empatados);
                    $elegidos = array_values(array_unique(array_map('intval', $tercerosElegidos)));
                    if (count($elegidos) === $cupos && !array_diff($elegidos, $ids)) {
                        $clasifican = array_merge($seguros, array_values(array_filter($empatados,
                            fn($s) => in_array((int) $s['jugador']->id, $elegidos, true))));
                    } else {
                        $res['empate_terceros'] = $ids;
                        $res['cupos_terceros']  = $cupos;
                    }
                } else {
                    $clasifican = [$t[0], $t[1]];
                }

                if ($clasifican) {
                    $idsClasif = array_map(fn($s) => $s['jugador']->id, $clasifican);
                    foreach ($res['terceros'] as &$s) $s['clasifica'] = in_array($s['jugador']->id, $idsClasif);
                    unset($s);
                    $res['marcados']  = array_merge($res['marcados'], $idsClasif);
                    $zonasConTercero = array_map(fn($s) => $s['zona'], $clasifican);
                }
            }
        }

        if (!$res['completo']) return $res;

        $id = fn(int $zona, int $lugar) => $pos[$zona][$lugar]?->id;

        if ($grupos->count() === 4) {
            $cuartos = [
                [$id(1, 1), $id(2, 2)],
                [$id(3, 1), $id(4, 2)],
                [$id(2, 1), $id(1, 2)],
                [$id(4, 1), $id(3, 2)],
            ];
        } else {
            if (count($zonasConTercero) !== 2) return $res;
            sort($zonasConTercero);
            [$a, $b] = $zonasConTercero;
            $c = $grupos->pluck('numero')->diff($zonasConTercero)->first();

            $cuartos = [
                [$id($a, 1), $id($b, 3)],
                [$id($c, 1), $id($b, 2)],
                [$id($b, 1), $id($a, 3)],
                [$id($a, 2), $id($c, 2)],
            ];
        }

        if (collect($cuartos)->flatten()->contains(null)) return $res;
        $res['cuartos'] = $cuartos;
        return $res;
    }
}
