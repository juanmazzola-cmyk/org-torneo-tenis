<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Master extends Model
{
    protected $fillable = ['torneo_id', 'categoria_id', 'estado'];

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
     */
    public function clasificacionDobles(?int $mejorSegundoElegido = null): array
    {
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
}
