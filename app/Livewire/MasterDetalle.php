<?php

namespace App\Livewire;

use App\Models\Master;
use App\Models\MasterFinal;
use App\Models\MasterGrupo;
use App\Models\MasterJugadorGrupo;
use App\Models\MasterPartido;
use App\Models\Ranking;
use App\Models\Jugador;
use App\Models\Torneo;
use Livewire\Component;

class MasterDetalle extends Component
{
    public Torneo $torneo;
    public Master $master;

    // Modal asignar jugador
    public bool   $modalAsignar    = false;
    public ?int   $asignarGrupoId  = null;
    public ?int   $asignarPosicion = null;
    public string $buscarJugador   = '';

    // Modal resultado zona
    public bool   $modalResultado = false;
    public ?int   $partidoId      = null;
    public string $resultado      = '';
    public string $ganadorId      = '';
    public bool   $esWO           = false;
    public bool   $esRetiro       = false;

    // Modal resultado fase final
    public bool   $modalResultadoFinal = false;
    public ?int   $finalId             = null;

    // Sets individuales (compartidos por ambos modales)
    public string $s1j1 = '', $s1j2 = '';
    public string $s2j1 = '', $s2j2 = '';
    public string $s3j1 = '', $s3j2 = '';

    // Confirmación quitar jugador
    public bool $confirmQuitarJugador = false;
    public ?int $quitarGrupoId        = null;
    public ?int $quitarJugadorId      = null;

    public function mount(Torneo $torneo, Master $master): void
    {
        $this->torneo = $torneo;
        $this->master = $master;
    }

    // ── Asignar jugador ────────────────────────────────────────────────

    public function abrirAsignar(int $grupoId, int $posicion): void
    {
        $this->asignarGrupoId  = $grupoId;
        $this->asignarPosicion = $posicion;
        $this->buscarJugador   = '';
        $this->modalAsignar    = true;
    }

    public function asignarJugador(int $jugadorId): void
    {
        $grupo = MasterGrupo::findOrFail($this->asignarGrupoId);

        $otroGrupo = $this->master->grupos()->where('id', '!=', $this->asignarGrupoId)->first();
        if ($otroGrupo?->jugadoresGrupo()->where('jugador_id', $jugadorId)->exists()) {
            session()->flash('error', 'El jugador ya está en la otra zona.');
            $this->resetAsignar();
            return;
        }

        $grupo->jugadoresGrupo()->where('posicion', $this->asignarPosicion)->delete();
        $grupo->jugadoresGrupo()->where('jugador_id', $jugadorId)->delete();

        MasterJugadorGrupo::create([
            'grupo_id'   => $this->asignarGrupoId,
            'jugador_id' => $jugadorId,
            'posicion'   => $this->asignarPosicion,
        ]);

        if ($grupo->fresh()->jugadoresGrupo()->count() === 4) {
            $grupo->generarPartidos();
        }

        $this->resetAsignar();
        session()->flash('ok', 'Jugador asignado.');
    }

    public function generarPartidos(int $grupoId): void
    {
        MasterGrupo::findOrFail($grupoId)->generarPartidos();
        session()->flash('ok', 'Partidos generados.');
    }

    public function confirmarQuitarJugador(int $grupoId, int $jugadorId): void
    {
        $this->quitarGrupoId        = $grupoId;
        $this->quitarJugadorId      = $jugadorId;
        $this->confirmQuitarJugador = true;
    }

    public function quitarJugador(): void
    {
        $grupo = MasterGrupo::findOrFail($this->quitarGrupoId);
        $grupo->partidos()->delete();
        $grupo->jugadoresGrupo()->where('jugador_id', $this->quitarJugadorId)->delete();

        // Si hay fase final, limpiarla
        $this->limpiarFaseFinal();

        $this->confirmQuitarJugador = false;
        $this->quitarGrupoId        = null;
        $this->quitarJugadorId      = null;
        session()->flash('ok', 'Jugador quitado.');
    }

    public function cancelarQuitarJugador(): void
    {
        $this->confirmQuitarJugador = false;
        $this->quitarGrupoId        = null;
        $this->quitarJugadorId      = null;
    }

    // ── Resultados zona ────────────────────────────────────────────────

    public function abrirResultado(int $partidoId): void
    {
        $partido = MasterPartido::findOrFail($partidoId);
        $this->partidoId = $partidoId;
        $this->ganadorId = (string) ($partido->ganador_id ?? '');
        $this->esWO      = $partido->resultado === 'W.O.';
        $this->esRetiro  = !$this->esWO && str_ends_with(trim($partido->resultado ?? ''), 'ret.');

        if (!$this->esWO) {
            $raw = preg_replace('/\s*ret\.\s*$/i', '', $partido->resultado ?? '');
            $this->resultado = $raw;
            $this->parsearSets($raw);
            // Si el ganador era jugador2, los sets almacenados están con el ganador primero;
            // invertimos los campos para que cada columna muestre los games reales del jugador.
            if ($partido->ganador_id && (string)$partido->jugador2_id === (string)$partido->ganador_id) {
                [$this->s1j1, $this->s1j2] = [$this->s1j2, $this->s1j1];
                [$this->s2j1, $this->s2j2] = [$this->s2j2, $this->s2j1];
                [$this->s3j1, $this->s3j2] = [$this->s3j2, $this->s3j1];
            }
        } else {
            $this->resultado = '';
        }
        $this->modalResultado = true;
    }

    public function guardarResultado(): void
    {
        $partido    = MasterPartido::findOrFail($this->partidoId);
        $flipSets   = !$this->esWO && (string)$partido->jugador2_id === (string)$this->ganadorId;
        $this->resultado = $this->buildResultado($flipSets);

        $this->validate([
            'resultado' => 'required|string|max:50',
            'ganadorId' => 'required|exists:jugadores,id',
        ], [
            'resultado.required' => 'Ingresá al menos el Set 1.',
            'ganadorId.required' => 'Seleccioná el ganador.',
        ]);

        $esJornada1  = $partido->jornada === 1;

        if ($esJornada1 && $partido->ganador_id) {
            $partido->grupo->limpiarCruces();
            $this->limpiarFaseFinal();
        }

        $partido->update([
            'resultado'  => trim($this->resultado),
            'ganador_id' => $this->ganadorId,
        ]);

        if ($esJornada1) {
            $partido->grupo->actualizarCruces();
        }

        $this->recalcularRankingMaster();
        $this->resetResultado();
        session()->flash('ok', 'Resultado guardado.');
    }

    public function anularResultado(int $partidoId): void
    {
        $partido = MasterPartido::findOrFail($partidoId);

        if ($partido->jornada === 1) {
            $partido->grupo->limpiarCruces();
        }

        // Si se anula cualquier resultado de zona, limpiar fase final
        $this->limpiarFaseFinal();

        $partido->update(['resultado' => null, 'ganador_id' => null]);
        $this->recalcularRankingMaster();
        session()->flash('ok', 'Resultado anulado.');
    }

    // ── Fase Final ─────────────────────────────────────────────────────

    public function generarFaseFinal(): void
    {
        $grupos = $this->master->grupos()->get();
        if ($grupos->count() !== 2) return;

        foreach ($grupos as $g) {
            if (!$g->estaCompleto()) {
                session()->flash('error', 'Completá todos los partidos de las zonas primero.');
                return;
            }
        }

        $zona1 = $grupos->firstWhere('numero', 1);
        $zona2 = $grupos->firstWhere('numero', 2);

        $pos1 = $zona1->calcularPosiciones();
        $pos2 = $zona2->calcularPosiciones();

        $p1z1 = $pos1[0]['jugador']->id ?? null; // 1° Zona 1
        $p2z1 = $pos1[1]['jugador']->id ?? null; // 2° Zona 1
        $p1z2 = $pos2[0]['jugador']->id ?? null; // 1° Zona 2
        $p2z2 = $pos2[1]['jugador']->id ?? null; // 2° Zona 2

        if (!$p1z1 || !$p2z1 || !$p1z2 || !$p2z2) {
            session()->flash('error', 'No se pudieron determinar los clasificados.');
            return;
        }

        // Limpiar fase final anterior
        $this->limpiarFaseFinal();

        // Semifinal 1: 1° Zona 1 vs 2° Zona 2
        MasterFinal::create([
            'master_id'   => $this->master->id,
            'tipo'        => 'semifinal_1',
            'jugador1_id' => $p1z1,
            'jugador2_id' => $p2z2,
        ]);

        // Semifinal 2: 1° Zona 2 vs 2° Zona 1
        MasterFinal::create([
            'master_id'   => $this->master->id,
            'tipo'        => 'semifinal_2',
            'jugador1_id' => $p1z2,
            'jugador2_id' => $p2z1,
        ]);

        // Final (vacía, se llena al terminar semis)
        MasterFinal::create([
            'master_id' => $this->master->id,
            'tipo'      => 'final',
        ]);

        $this->master->update(['estado' => 'en_curso']);
        session()->flash('ok', 'Fase final generada. Ingresá los resultados de las semifinales.');
    }

    public function abrirResultadoFinal(int $finalId): void
    {
        $final = MasterFinal::findOrFail($finalId);
        $this->finalId   = $finalId;
        $this->ganadorId = (string) ($final->ganador_id ?? '');
        $this->esWO      = $final->resultado === 'W.O.';
        $this->esRetiro  = !$this->esWO && str_ends_with(trim($final->resultado ?? ''), 'ret.');

        if (!$this->esWO) {
            $raw = preg_replace('/\s*ret\.\s*$/i', '', $final->resultado ?? '');
            $this->resultado = $raw;
            $this->parsearSets($raw);
            if ($final->ganador_id && (string)$final->jugador2_id === (string)$final->ganador_id) {
                [$this->s1j1, $this->s1j2] = [$this->s1j2, $this->s1j1];
                [$this->s2j1, $this->s2j2] = [$this->s2j2, $this->s2j1];
                [$this->s3j1, $this->s3j2] = [$this->s3j2, $this->s3j1];
            }
        } else {
            $this->resultado = '';
        }
        $this->modalResultadoFinal = true;
    }

    public function guardarResultadoFinal(): void
    {
        $final    = MasterFinal::findOrFail($this->finalId);
        $flipSets = !$this->esWO && (string)$final->jugador2_id === (string)$this->ganadorId;
        $this->resultado = $this->buildResultado($flipSets);

        $this->validate([
            'resultado' => 'required|string|max:50',
            'ganadorId' => 'required|exists:jugadores,id',
        ], [
            'resultado.required' => 'Ingresá al menos el Set 1.',
            'ganadorId.required' => 'Seleccioná el ganador.',
        ]);

        // Si se edita una semi y ya había final con resultado, limpiar final
        if (in_array($final->tipo, ['semifinal_1', 'semifinal_2'])) {
            MasterFinal::where('master_id', $this->master->id)
                ->where('tipo', 'final')
                ->update(['jugador1_id' => null, 'jugador2_id' => null, 'resultado' => null, 'ganador_id' => null]);
        }

        $final->update([
            'resultado'  => trim($this->resultado),
            'ganador_id' => $this->ganadorId,
        ]);

        // Si era una semi, intentar armar la final y actualizar ranking parcial
        if (in_array($final->tipo, ['semifinal_1', 'semifinal_2'])) {
            $this->actualizarFinal();
            $this->recalcularRankingMaster();
        }

        // Si era la final, guardar ranking y marcar como finalizado
        if ($final->tipo === 'final') {
            $this->recalcularRankingMaster();
            $this->master->update(['estado' => 'finalizado']);
        }

        $this->resetResultadoFinal();
        session()->flash('ok', 'Resultado guardado.');
    }

    public function anularResultadoFinal(int $finalId): void
    {
        $final = MasterFinal::findOrFail($finalId);

        // Si se anula una semi, limpiar la final también
        if (in_array($final->tipo, ['semifinal_1', 'semifinal_2'])) {
            MasterFinal::where('master_id', $this->master->id)
                ->where('tipo', 'final')
                ->update(['jugador1_id' => null, 'jugador2_id' => null, 'resultado' => null, 'ganador_id' => null]);
        }

        if ($final->tipo === 'final') {
            $this->master->update(['estado' => 'en_curso']);
        }

        $final->update(['resultado' => null, 'ganador_id' => null]);
        $this->recalcularRankingMaster();
        session()->flash('ok', 'Resultado anulado.');
    }

    private function actualizarFinal(): void
    {
        $semi1 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_1')->first();
        $semi2 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_2')->first();

        if (!$semi1?->ganador_id || !$semi2?->ganador_id) return;

        MasterFinal::where('master_id', $this->master->id)
            ->where('tipo', 'final')
            ->update([
                'jugador1_id' => $semi1->ganador_id,
                'jugador2_id' => $semi2->ganador_id,
                'resultado'   => null,
                'ganador_id'  => null,
            ]);
    }

    private function recalcularRankingMaster(): void
    {
        Ranking::where('torneo_id', $this->master->torneo_id)
            ->where('categoria_id', $this->master->categoria_id)
            ->delete();

        $puntos = [];

        $semi1 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_1')->first();
        $semi2 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_2')->first();
        $final = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'final')->first();

        // Jugadores en fase final (para no darles 40 pts de zona)
        $enFaseFinal = array_filter(array_unique([
            $semi1?->jugador1_id, $semi1?->jugador2_id,
            $semi2?->jugador1_id, $semi2?->jugador2_id,
        ]));

        if ($final?->ganador_id) {
            $puntos[$final->ganador_id]  = 100;
            $puntos[$final->perdedor_id()] = 80;
        }
        if ($semi1?->ganador_id) $puntos[$semi1->perdedor_id()] = 60;
        if ($semi2?->ganador_id) $puntos[$semi2->perdedor_id()] = 60;

        // No clasificados (3° y 4° de zonas completas)
        foreach ($this->master->grupos()->get() as $grupo) {
            if (!$grupo->estaCompleto()) continue;
            foreach ($grupo->calcularPosiciones() as $s) {
                $jId = $s['jugador']->id;
                if (!in_array($jId, $enFaseFinal) && !isset($puntos[$jId])) {
                    $puntos[$jId] = 40;
                }
            }
        }

        foreach ($puntos as $jugadorId => $pts) {
            if (!$jugadorId) continue;
            Ranking::updateOrCreate(
                [
                    'torneo_id'    => $this->master->torneo_id,
                    'jugador_id'   => $jugadorId,
                    'categoria_id' => $this->master->categoria_id,
                ],
                [
                    'ronda_eliminado' => match(true) {
                        $pts >= 100 => 0,
                        $pts >= 80  => 1,
                        $pts >= 60  => 2,
                        default     => 3,
                    },
                    'puntos' => $pts,
                ]
            );
        }
    }

    private function limpiarFaseFinal(): void
    {
        MasterFinal::where('master_id', $this->master->id)->delete();
        Ranking::where('torneo_id', $this->master->torneo_id)
            ->where('categoria_id', $this->master->categoria_id)
            ->delete();
        $this->master->update(['estado' => 'pendiente']);
    }

    // ── Reset helpers ──────────────────────────────────────────────────

    private function resetAsignar(): void
    {
        $this->modalAsignar    = false;
        $this->asignarGrupoId  = null;
        $this->asignarPosicion = null;
        $this->buscarJugador   = '';
    }

    private function resetResultado(): void
    {
        $this->modalResultado = false;
        $this->partidoId      = null;
        $this->resultado      = '';
        $this->ganadorId      = '';
        $this->esWO           = false;
        $this->esRetiro       = false;
        $this->resetSets();
    }

    private function resetResultadoFinal(): void
    {
        $this->modalResultadoFinal = false;
        $this->finalId             = null;
        $this->resultado           = '';
        $this->ganadorId           = '';
        $this->esWO                = false;
        $this->esRetiro            = false;
        $this->resetSets();
    }

    // ── Helpers sets ──────────────────────────────────────────────────

    private function parsearSets(string $resultado): void
    {
        $this->resetSets();
        if (!$resultado) return;

        foreach (explode(' ', trim($resultado)) as $i => $set) {
            if (!preg_match('/^(\d+)-(\d+)$/', $set, $m)) continue;
            match($i) {
                0 => [$this->s1j1, $this->s1j2] = [$m[1], $m[2]],
                1 => [$this->s2j1, $this->s2j2] = [$m[1], $m[2]],
                2 => [$this->s3j1, $this->s3j2] = [$m[1], $m[2]],
                default => null,
            };
        }
    }

    private function buildResultado(bool $flipSets = false): string
    {
        if ($this->esWO) return 'W.O.';
        $sets = [];
        if ($this->s1j1 !== '' && $this->s1j2 !== '') {
            $sets[] = $flipSets ? "{$this->s1j2}-{$this->s1j1}" : "{$this->s1j1}-{$this->s1j2}";
        }
        if ($this->s2j1 !== '' && $this->s2j2 !== '') {
            $sets[] = $flipSets ? "{$this->s2j2}-{$this->s2j1}" : "{$this->s2j1}-{$this->s2j2}";
        }
        if ($this->s3j1 !== '' && $this->s3j2 !== '') {
            $sets[] = $flipSets ? "{$this->s3j2}-{$this->s3j1}" : "{$this->s3j1}-{$this->s3j2}";
        }
        $result = implode(' ', $sets);
        if ($this->esRetiro) {
            return $result !== '' ? $result . ' ret.' : 'ret.';
        }
        return $result;
    }

    private function resetSets(): void
    {
        $this->s1j1 = $this->s1j2 = '';
        $this->s2j1 = $this->s2j2 = '';
        $this->s3j1 = $this->s3j2 = '';
    }

    public function cerrarModales(): void
    {
        $this->resetAsignar();
        $this->resetResultado();
        $this->resetResultadoFinal();
    }

    // ── Render ─────────────────────────────────────────────────────────

    public function render()
    {
        $grupos = $this->master->grupos()
            ->with([
                'jugadoresGrupo.jugador',
                'partidos.jugador1',
                'partidos.jugador2',
                'partidos.ganador',
            ])
            ->get();

        $posiciones = [];
        foreach ($grupos as $grupo) {
            $posiciones[$grupo->id] = $grupo->calcularPosiciones();
        }

        $jugadoresAsignados = collect();
        foreach ($grupos as $grupo) {
            $jugadoresAsignados = $jugadoresAsignados->merge(
                $grupo->jugadoresGrupo->pluck('jugador_id')
            );
        }

        $jugadoresDisponibles = Jugador::whereHas('inscripciones', fn($q) =>
                $q->where('torneo_id', $this->master->torneo_id)
                  ->where('categoria_id', $this->master->categoria_id)
            )
            ->when($this->buscarJugador, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('apellido', 'like', '%' . $this->buscarJugador . '%')
                       ->orWhere('nombre', 'like', '%' . $this->buscarJugador . '%')
                )
            )
            ->orderBy('apellido')->orderBy('nombre')
            ->get();

        $partidoActivo = $this->partidoId
            ? MasterPartido::with(['jugador1', 'jugador2'])->find($this->partidoId)
            : null;

        $finalActiva = $this->finalId
            ? MasterFinal::with(['jugador1', 'jugador2'])->find($this->finalId)
            : null;

        $faseFinal = MasterFinal::with(['jugador1', 'jugador2', 'ganador'])
            ->where('master_id', $this->master->id)
            ->orderByRaw("FIELD(tipo, 'semifinal_1', 'semifinal_2', 'final')")
            ->get();

        $ambasZonasCompletas = $grupos->count() === 2
            && $grupos->every(fn($g) => $g->estaCompleto());

        return view('livewire.master-detalle', compact(
            'grupos', 'posiciones',
            'jugadoresDisponibles', 'jugadoresAsignados',
            'partidoActivo', 'faseFinal', 'finalActiva',
            'ambasZonasCompletas'
        ))->layout('layouts.app');
    }
}
