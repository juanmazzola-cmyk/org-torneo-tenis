<?php

namespace App\Livewire;

use App\Models\Master;
use App\Models\MasterFinal;
use App\Models\MasterGrupo;
use App\Models\MasterJugadorGrupo;
use App\Models\MasterPartido;
use App\Models\Ranking;
use App\Models\Torneo;
use App\Models\Inscripcion;
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
        $this->partidoId      = $partidoId;
        $this->resultado      = $partido->resultado ?? '';
        $this->ganadorId      = (string) ($partido->ganador_id ?? '');
        $this->parsearSets($this->resultado);
        $this->modalResultado = true;
    }

    public function guardarResultado(): void
    {
        $this->resultado = $this->buildResultado();

        $this->validate([
            'resultado' => 'required|string|max:50',
            'ganadorId' => 'required|exists:jugadores,id',
        ], [
            'resultado.required' => 'Ingresá al menos el Set 1.',
            'ganadorId.required' => 'Seleccioná el ganador.',
        ]);

        $partido     = MasterPartido::findOrFail($this->partidoId);
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
        $this->finalId             = $finalId;
        $this->resultado           = $final->resultado ?? '';
        $this->ganadorId           = (string) ($final->ganador_id ?? '');
        $this->parsearSets($this->resultado);
        $this->modalResultadoFinal = true;
    }

    public function guardarResultadoFinal(): void
    {
        $this->resultado = $this->buildResultado();

        $this->validate([
            'resultado' => 'required|string|max:50',
            'ganadorId' => 'required|exists:jugadores,id',
        ], [
            'resultado.required' => 'Ingresá al menos el Set 1.',
            'ganadorId.required' => 'Seleccioná el ganador.',
        ]);

        $final = MasterFinal::findOrFail($this->finalId);

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

        // Si era una semi, intentar armar la final
        if (in_array($final->tipo, ['semifinal_1', 'semifinal_2'])) {
            $this->actualizarFinal();
        }

        // Si era la final, guardar ranking
        if ($final->tipo === 'final') {
            $this->guardarRanking();
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

        // Si se anula la final, limpiar rankings y volver a en_curso
        if ($final->tipo === 'final') {
            Ranking::where('torneo_id', $this->master->torneo_id)
                ->where('categoria_id', $this->master->categoria_id)
                ->delete();
            $this->master->update(['estado' => 'en_curso']);
        }

        $final->update(['resultado' => null, 'ganador_id' => null]);
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

    private function guardarRanking(): void
    {
        $semi1 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_1')->first();
        $semi2 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_2')->first();
        $final = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'final')->first();

        if (!$semi1 || !$semi2 || !$final?->ganador_id) return;

        // Borrar rankings anteriores de este master
        Ranking::where('torneo_id', $this->master->torneo_id)
            ->where('categoria_id', $this->master->categoria_id)
            ->delete();

        $puntos = [
            $final->ganador_id              => 100, // Campeón
            $final->perdedor_id()           => 80,  // Finalista
            $semi1->perdedor_id()           => 60,  // Semifinalista
            $semi2->perdedor_id()           => 60,  // Semifinalista
        ];

        // No clasificados (3° y 4° de cada zona)
        $grupos = $this->master->grupos()->get();
        $clasificados = array_filter(array_keys($puntos));

        foreach ($grupos as $grupo) {
            $pos = $grupo->calcularPosiciones();
            foreach ($pos as $i => $s) {
                $jId = $s['jugador']->id;
                if (!in_array($jId, $clasificados)) {
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
                    'ronda_eliminado' => match($pts) {
                        100 => 0,
                        80  => 1,
                        60  => 2,
                        default => 3,
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
        $this->resetSets();
    }

    private function resetResultadoFinal(): void
    {
        $this->modalResultadoFinal = false;
        $this->finalId             = null;
        $this->resultado           = '';
        $this->ganadorId           = '';
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

    private function buildResultado(): string
    {
        $sets = [];
        if ($this->s1j1 !== '' && $this->s1j2 !== '') $sets[] = "{$this->s1j1}-{$this->s1j2}";
        if ($this->s2j1 !== '' && $this->s2j2 !== '') $sets[] = "{$this->s2j1}-{$this->s2j2}";
        if ($this->s3j1 !== '' && $this->s3j2 !== '') $sets[] = "{$this->s3j1}-{$this->s3j2}";
        return implode(' ', $sets);
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

        $jugadoresDisponibles = Inscripcion::with('jugador')
            ->where('torneo_id', $this->torneo->id)
            ->where('categoria_id', $this->master->categoria_id)
            ->when($this->buscarJugador, fn($q) =>
                $q->whereHas('jugador', fn($q2) =>
                    $q2->where('apellido', 'like', '%' . $this->buscarJugador . '%')
                       ->orWhere('nombre', 'like', '%' . $this->buscarJugador . '%')
                )
            )
            ->get()
            ->map(fn($i) => $i->jugador)
            ->sortBy('apellido')
            ->values();

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
