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

    /** Genera los 6 partidos del round robin una vez asignados los 4 jugadores */
    public function generarPartidos(): void
    {
        $jugadores = $this->jugadoresGrupo()->with('jugador')->orderBy('posicion')->get();
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
    }

    /** Limpia jornadas 2 y 3 (cuando se anula un resultado de jornada 1) */
    public function limpiarCruces(): void
    {
        $this->partidos()->whereIn('jornada', [2, 3])->update([
            'jugador1_id' => null,
            'jugador2_id' => null,
            'resultado'   => null,
            'ganador_id'  => null,
        ]);
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
            if ($b['puntos']   !== $a['puntos'])   return $b['puntos']   - $a['puntos'];
            if ($b['dif_part'] !== $a['dif_part']) return $b['dif_part'] - $a['dif_part'];
            if ($b['dif_set']  !== $a['dif_set'])  return $b['dif_set']  - $a['dif_set'];
            return $b['dif_game'] - $a['dif_game'];
        });

        return array_values($stats);
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
