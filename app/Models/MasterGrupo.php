<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterGrupo extends Model
{
    protected $fillable = ['master_id', 'numero', 'nombre'];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function jugadoresGrupo(): HasMany
    {
        return $this->hasMany(MasterJugadorGrupo::class, 'grupo_id')->orderBy('posicion');
    }

    public function partidos(): HasMany
    {
        return $this->hasMany(MasterPartido::class, 'grupo_id')
            ->orderBy('jornada')
            ->orderBy('orden');
    }

    /**
     * Genera los 6 partidos del round robin una vez asignados los 4 jugadores.
     * En dobles también se admite una zona de 3 parejas (3 partidos, ver generarPartidosDeTres()).
     */
    public function generarPartidos(): void
    {
        $jugadores = $this->jugadoresGrupo()->with('jugador')->orderBy('posicion')->get();

        if ($jugadores->count() === 3 && $this->master->categoria->es_dobles) {
            $this->generarPartidosDeTres($jugadores->values());
            return;
        }

        if ($jugadores->count() !== 4) return;

        $this->partidos()->delete();

        $p = $jugadores->keyBy('posicion'); // posicion => MasterJugadorGrupo

        // Jornada 1: pos1 vs pos2, pos3 vs pos4
        MasterPartido::create([
            'grupo_id'    => $this->id,
            'jornada'     => 1,
            'orden'       => 1,
            'jugador1_id' => $p[1]->jugador_id,
            'jugador2_id' => $p[2]->jugador_id,
        ]);
        MasterPartido::create([
            'grupo_id'    => $this->id,
            'jornada'     => 1,
            'orden'       => 2,
            'jugador1_id' => $p[3]->jugador_id,
            'jugador2_id' => $p[4]->jugador_id,
        ]);

        // Jornadas 2 y 3 se completan con jugadores cuando termina jornada 1
        foreach ([2, 3] as $jornada) {
            foreach ([1, 2] as $orden) {
                MasterPartido::create([
                    'grupo_id' => $this->id,
                    'jornada'  => $jornada,
                    'orden'    => $orden,
                ]);
            }
        }
    }

    /**
     * Zona de 3 (solo dobles): calendario fijo J1 1-2, J2 2-3, J3 1-3 (en orden de posición),
     * descansa una pareja por jornada. No depende de resultados, así que no hay cruces que rearmar.
     */
    private function generarPartidosDeTres($jugadores): void
    {
        $this->partidos()->delete();

        foreach ([1 => [0, 1], 2 => [1, 2], 3 => [0, 2]] as $jornada => [$a, $b]) {
            MasterPartido::create([
                'grupo_id'    => $this->id,
                'jornada'     => $jornada,
                'orden'       => 1,
                'jugador1_id' => $jugadores[$a]->jugador_id,
                'jugador2_id' => $jugadores[$b]->jugador_id,
            ]);
        }
    }

    /**
     * Zona de 3 parejas (dobles). Se cuenta por partidos y NO por filas de master_jugadores_grupo:
     * con un retiro, una zona de 3 pasa a tener 4 filas (lesionado + suplente).
     */
    public function esDeTres(): bool
    {
        return $this->partidos()->count() === 3;
    }

    /** Zona de 3: [jornada => jugador_id que descansa], con el suplente en lugar del lesionado */
    public function descansos(): array
    {
        if (!$this->esDeTres()) return [];

        $mapa = $this->reemplazos();
        $real = fn($id) => $mapa[$id] ?? $id;
        $porJornada = $this->partidos()->get()->keyBy('jornada')
            ->map(fn($p) => [$real($p->jugador1_id), $real($p->jugador2_id)]);

        // Descansa en una jornada quien juega en las otras dos
        $res = [];
        foreach ([1, 2, 3] as $jornada) {
            $otras = $porJornada->except($jornada)->values();
            if ($otras->count() !== 2) continue;
            $comun = array_values(array_intersect($otras[0], $otras[1]));
            if ($comun) $res[$jornada] = $comun[0];
        }
        return $res;
    }

    /** Asigna jugadores a jornadas 2 y 3 según resultados de jornada 1 */
    public function actualizarCruces(): void
    {
        $j1 = $this->partidos()->where('jornada', 1)->orderBy('orden')->get();
        if ($j1->count() !== 2) return;

        $m1 = $j1->firstWhere('orden', 1);
        $m2 = $j1->firstWhere('orden', 2);

        if (!$m1?->ganador_id || !$m2?->ganador_id) return;

        $w1 = $m1->ganador_id;
        $l1 = ($m1->jugador1_id == $w1) ? $m1->jugador2_id : $m1->jugador1_id;
        $w2 = $m2->ganador_id;
        $l2 = ($m2->jugador1_id == $w2) ? $m2->jugador2_id : $m2->jugador1_id;

        // Jornada 2: ganadores entre sí, perdedores entre sí
        $this->partidos()->where('jornada', 2)->where('orden', 1)
            ->update(['jugador1_id' => $w1, 'jugador2_id' => $w2, 'resultado' => null, 'ganador_id' => null]);
        $this->partidos()->where('jornada', 2)->where('orden', 2)
            ->update(['jugador1_id' => $l1, 'jugador2_id' => $l2, 'resultado' => null, 'ganador_id' => null]);

        // Jornada 3: cruce (ganador1 vs perdedor2, perdedor1 vs ganador2)
        $this->partidos()->where('jornada', 3)->where('orden', 1)
            ->update(['jugador1_id' => $w1, 'jugador2_id' => $l2, 'resultado' => null, 'ganador_id' => null]);
        $this->partidos()->where('jornada', 3)->where('orden', 2)
            ->update(['jugador1_id' => $l1, 'jugador2_id' => $w2, 'resultado' => null, 'ganador_id' => null]);

        // Los cruces se arman con los ids de J1; si hubo retiros, el suplente ocupa el lugar
        $this->aplicarReemplazos();
    }

    /** Mapa [lesionado_id => suplente_id], resolviendo cadenas (suplente que también se retira) */
    public function reemplazos(): array
    {
        $directo = $this->jugadoresGrupo()
            ->whereNotNull('reemplaza_a_id')
            ->pluck('jugador_id', 'reemplaza_a_id')
            ->all();

        $mapa = [];
        foreach ($directo as $lesionado => $suplente) {
            $final = $suplente;
            $vistos = [$lesionado => true];
            while (isset($directo[$final]) && !isset($vistos[$final])) {
                $vistos[$final] = true;
                $final = $directo[$final];
            }
            $mapa[$lesionado] = $final;
        }
        return $mapa;
    }

    /** Pone al suplente en lugar del lesionado en los partidos pendientes (los jugados no se tocan) */
    public function aplicarReemplazos(): void
    {
        foreach ($this->reemplazos() as $lesionado => $suplente) {
            $this->partidos()->whereNull('ganador_id')->where('jugador1_id', $lesionado)
                ->update(['jugador1_id' => $suplente]);
            $this->partidos()->whereNull('ganador_id')->where('jugador2_id', $lesionado)
                ->update(['jugador2_id' => $suplente]);
        }
    }

    public function tieneRetirado(): bool
    {
        return $this->jugadoresGrupo()->where('retirado', true)->exists();
    }

    public function jornada1Completa(): bool
    {
        $j1 = $this->partidos()->where('jornada', 1)->get();
        $esperados = $this->esDeTres() ? 1 : 2;
        return $j1->count() === $esperados && $j1->every(fn($p) => $p->ganador_id);
    }

    /**
     * Con un retirado en la zona, J1 queda congelada si ya hay resultados en J2/J3.
     * En una zona de 3 no: J2/J3 no dependen de J1.
     */
    public function jornada1Bloqueada(): bool
    {
        return !$this->esDeTres()
            && $this->tieneRetirado()
            && $this->partidos()->whereIn('jornada', [2, 3])->whereNotNull('ganador_id')->exists();
    }

    /** Limpia jornadas 2 y 3 (cuando se anula un resultado de jornada 1) */
    public function limpiarCruces(): void
    {
        // En una zona de 3 los cruces son fijos: limpiarlos dejaría J2/J3 sin jugadores para siempre
        if ($this->esDeTres()) return;

        $this->partidos()->whereIn('jornada', [2, 3])->update([
            'jugador1_id' => null,
            'jugador2_id' => null,
            'resultado'   => null,
            'ganador_id'  => null,
        ]);
    }

    /** Verifica si todos los partidos del grupo tienen resultado */
    public function estaCompleto(): bool
    {
        $total = $this->partidos()->count();
        if ($total < ($total === 3 ? 3 : 6)) return false; // zona de 3 (dobles): 3 partidos
        return $this->partidos()->whereNull('ganador_id')->count() === 0;
    }

    /** Calcula tabla de posiciones */
    public function calcularPosiciones(): array
    {
        $jugadores = $this->jugadoresGrupo()->with('jugador')->orderBy('posicion')->get();
        if ($jugadores->isEmpty()) return [];

        $stats = [];
        foreach ($jugadores as $jg) {
            $stats[$jg->jugador_id] = [
                'jugador'   => $jg->jugador,
                'posicion'  => $jg->posicion,
                'retirado'  => (bool) $jg->retirado,
                'pj' => 0, 'pg' => 0, 'pp' => 0,
                'sg' => 0, 'sp' => 0,
                'gg' => 0, 'gp' => 0,
                'puntos' => 0,
            ];
        }

        $partidos = $this->partidos()->whereNotNull('ganador_id')->get();

        foreach ($partidos as $partido) {
            $j1 = $partido->jugador1_id;
            $j2 = $partido->jugador2_id;

            if (!isset($stats[$j1]) || !isset($stats[$j2])) continue;

            [$sg1, $sp1, $gg1, $gp1] = $this->parseResultado($partido->resultado);

            // El resultado se guarda con los games del ganador primero; si ganó
            // jugador2, invertimos para que los valores queden desde la óptica de jugador1.
            if ($partido->ganador_id == $j2) {
                [$sg1, $sp1, $gg1, $gp1] = [$sp1, $sg1, $gp1, $gg1];
            }

            $stats[$j1]['pj']++; $stats[$j2]['pj']++;
            $stats[$j1]['sg'] += $sg1; $stats[$j1]['sp'] += $sp1;
            $stats[$j2]['sg'] += $sp1; $stats[$j2]['sp'] += $sg1;
            $stats[$j1]['gg'] += $gg1; $stats[$j1]['gp'] += $gp1;
            $stats[$j2]['gg'] += $gp1; $stats[$j2]['gp'] += $gg1;

            if ($partido->ganador_id == $j1) {
                $stats[$j1]['pg']++; $stats[$j2]['pp']++;
                $stats[$j1]['puntos'] += 2;
            } else {
                $stats[$j2]['pg']++; $stats[$j1]['pp']++;
                $stats[$j2]['puntos'] += 2;
            }
        }

        foreach ($stats as &$s) {
            $s['dif_part'] = $s['pg'] - $s['pp'];
            $s['dif_set']  = $s['sg'] - $s['sp'];
            $s['dif_game'] = $s['gg'] - $s['gp'];
        }

        usort($stats, function ($a, $b) {
            // Los retirados van siempre al final, sin importar los puntos
            if ($a['retirado'] !== $b['retirado']) return $a['retirado'] <=> $b['retirado'];
            if ($b['puntos']  !== $a['puntos'])   return $b['puntos']   - $a['puntos'];
            if ($b['dif_part'] !== $a['dif_part']) return $b['dif_part'] - $a['dif_part'];
            if ($b['dif_set']  !== $a['dif_set'])  return $b['dif_set']  - $a['dif_set'];
            return $b['dif_game'] - $a['dif_game'];
        });

        return array_values($stats);
    }

    /**
     * Promedios para comparar parejas de zonas de distinto tamaño (mejor segundo en dobles):
     * puntos por partido jugado, % de sets ganados y % de games ganados, con TODOS los partidos
     * jugados (con un retiro, cada uno con los que jugó). El W.O. cuenta como 6-0 6-0 para el
     * ganador solo acá; la tabla de zona (calcularPosiciones) no cambia.
     * Devuelve [jugador_id => [pj, pts, sg, sp, gg, gp, prom_pts, pct_sets, pct_games]].
     */
    public function promedios(): array
    {
        $stats = [];
        foreach ($this->jugadoresGrupo()->get() as $jg) {
            $stats[$jg->jugador_id] = ['pj' => 0, 'pts' => 0, 'sg' => 0, 'sp' => 0, 'gg' => 0, 'gp' => 0];
        }

        foreach ($this->partidos()->whereNotNull('ganador_id')->get() as $partido) {
            $j1 = $partido->jugador1_id;
            $j2 = $partido->jugador2_id;
            if (!isset($stats[$j1]) || !isset($stats[$j2])) continue;

            // El resultado se guarda con el ganador primero
            [$sg, $sp, $gg, $gp] = $partido->resultado === 'W.O.'
                ? [2, 0, 12, 0]
                : $this->parseResultado($partido->resultado);

            $ganador  = $partido->ganador_id == $j1 ? $j1 : $j2;
            $perdedor = $ganador == $j1 ? $j2 : $j1;

            $stats[$ganador]['pj']++;  $stats[$ganador]['pts'] += 2;
            $stats[$ganador]['sg'] += $sg;  $stats[$ganador]['sp'] += $sp;
            $stats[$ganador]['gg'] += $gg;  $stats[$ganador]['gp'] += $gp;

            $stats[$perdedor]['pj']++;
            $stats[$perdedor]['sg'] += $sp; $stats[$perdedor]['sp'] += $sg;
            $stats[$perdedor]['gg'] += $gp; $stats[$perdedor]['gp'] += $gg;
        }

        foreach ($stats as &$s) {
            $s['prom_pts']  = $s['pj'] ? $s['pts'] / $s['pj'] : 0;
            $s['pct_sets']  = ($s['sg'] + $s['sp']) ? 100 * $s['sg'] / ($s['sg'] + $s['sp']) : 0;
            $s['pct_games'] = ($s['gg'] + $s['gp']) ? 100 * $s['gg'] / ($s['gg'] + $s['gp']) : 0;
        }
        unset($s);

        return $stats;
    }

    /**
     * Orden descendente por pts/PJ, después % sets y % games. Compara las fracciones
     * multiplicando en cruz (sin decimales) para que un empate real dé exactamente 0.
     */
    public static function compararPromedios(array $a, array $b): int
    {
        $comparar = function (int $na, int $da, int $nb, int $db): int {
            if (!$da) [$na, $da] = [0, 1];
            if (!$db) [$nb, $db] = [0, 1];
            return $nb * $da <=> $na * $db;
        };

        return $comparar($a['pts'], $a['pj'], $b['pts'], $b['pj'])
            ?: $comparar($a['sg'], $a['sg'] + $a['sp'], $b['sg'], $b['sg'] + $b['sp'])
            ?: $comparar($a['gg'], $a['gg'] + $a['gp'], $b['gg'], $b['gg'] + $b['gp']);
    }

    private function parseResultado(?string $resultado): array
    {
        if (!$resultado) return [0, 0, 0, 0];

        $sg = $sp = $gg = $gp = 0;
        foreach (explode(' ', trim($resultado)) as $set) {
            if (!preg_match('/^(\d+)-(\d+)$/', $set, $m)) continue;
            $g1 = (int) $m[1];
            $g2 = (int) $m[2];
            $gg += $g1;
            $gp += $g2;
            if ($g1 > $g2) $sg++;
            else            $sp++;
        }

        return [$sg, $sp, $gg, $gp];
    }
}
