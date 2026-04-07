<?php

namespace App\Livewire;

use App\Models\Master;
use App\Models\MasterGrupo;
use App\Models\MasterJugadorGrupo;
use App\Models\MasterPartido;
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
    public string $asignarJugadorId = '';
    public string $buscarJugador    = '';

    // Modal resultado
    public bool   $modalResultado  = false;
    public ?int   $partidoId       = null;
    public string $resultado       = '';
    public string $ganadorId       = '';

    // Confirmación quitar jugador
    public bool  $confirmQuitarJugador = false;
    public ?int  $quitarGrupoId        = null;
    public ?int  $quitarJugadorId      = null;

    public function mount(Torneo $torneo, Master $master): void
    {
        $this->torneo  = $torneo;
        $this->master  = $master;
    }

    // ── Asignar jugador ────────────────────────────────────────────────

    public function abrirAsignar(int $grupoId, int $posicion): void
    {
        $this->asignarGrupoId   = $grupoId;
        $this->asignarPosicion  = $posicion;
        $this->asignarJugadorId = '';
        $this->buscarJugador    = '';
        $this->modalAsignar     = true;
    }

    public function guardarAsignacion(): void
    {
        $this->validate([
            'asignarJugadorId' => 'required|exists:jugadores,id',
        ], ['asignarJugadorId.required' => 'Seleccioná un jugador.']);

        $grupo = MasterGrupo::findOrFail($this->asignarGrupoId);

        // Verificar que no esté ya en este grupo
        if ($grupo->jugadoresGrupo()->where('jugador_id', $this->asignarJugadorId)->exists()) {
            $this->addError('asignarJugadorId', 'El jugador ya está en esta zona.');
            return;
        }

        // Verificar que no esté en el otro grupo del mismo master
        $otroGrupo = $this->master->grupos()
            ->where('id', '!=', $this->asignarGrupoId)
            ->first();
        if ($otroGrupo?->jugadoresGrupo()->where('jugador_id', $this->asignarJugadorId)->exists()) {
            $this->addError('asignarJugadorId', 'El jugador ya está en la otra zona.');
            return;
        }

        // Eliminar si ya había alguien en esa posición
        $grupo->jugadoresGrupo()->where('posicion', $this->asignarPosicion)->delete();

        MasterJugadorGrupo::create([
            'grupo_id'   => $this->asignarGrupoId,
            'jugador_id' => $this->asignarJugadorId,
            'posicion'   => $this->asignarPosicion,
        ]);

        // Si el grupo ya tiene 4 jugadores, regenerar partidos
        if ($grupo->jugadoresGrupo()->count() === 4) {
            $grupo->generarPartidos();
        }

        $this->resetAsignar();
        session()->flash('ok', 'Jugador asignado.');
    }

    public function generarPartidos(int $grupoId): void
    {
        $grupo = MasterGrupo::findOrFail($grupoId);
        $grupo->generarPartidos();
        session()->flash('ok', 'Partidos generados correctamente.');
    }

    public function confirmarQuitarJugador(int $grupoId, int $jugadorId): void
    {
        $this->quitarGrupoId    = $grupoId;
        $this->quitarJugadorId  = $jugadorId;
        $this->confirmQuitarJugador = true;
    }

    public function quitarJugador(): void
    {
        $grupo = MasterGrupo::findOrFail($this->quitarGrupoId);

        // Borrar partidos del grupo (se regenerarán cuando se reasignen)
        $grupo->partidos()->delete();

        // Quitar jugador
        $grupo->jugadoresGrupo()->where('jugador_id', $this->quitarJugadorId)->delete();

        $this->reset(['confirmQuitarJugador', 'quitarGrupoId', 'quitarJugadorId']);
        session()->flash('ok', 'Jugador quitado. Reasigná los 4 jugadores para regenerar los partidos.');
    }

    public function cancelarQuitarJugador(): void
    {
        $this->reset(['confirmQuitarJugador', 'quitarGrupoId', 'quitarJugadorId']);
    }

    // ── Resultados ─────────────────────────────────────────────────────

    public function abrirResultado(int $partidoId): void
    {
        $partido = MasterPartido::findOrFail($partidoId);
        $this->partidoId  = $partidoId;
        $this->resultado  = $partido->resultado ?? '';
        $this->ganadorId  = (string) ($partido->ganador_id ?? '');
        $this->modalResultado = true;
    }

    public function guardarResultado(): void
    {
        $this->validate([
            'resultado' => 'required|string|max:50',
            'ganadorId' => 'required|exists:jugadores,id',
        ], [
            'resultado.required' => 'Ingresá el resultado.',
            'ganadorId.required' => 'Seleccioná el ganador.',
        ]);

        $partido = MasterPartido::findOrFail($this->partidoId);
        $esJornada1 = $partido->jornada === 1;

        // Si se cambió un resultado de jornada 1, limpiar cruces primero
        if ($esJornada1 && $partido->ganador_id) {
            $partido->grupo->limpiarCruces();
        }

        $partido->update([
            'resultado'  => trim($this->resultado),
            'ganador_id' => $this->ganadorId,
        ]);

        // Si era jornada 1, actualizar jornadas 2 y 3
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

        $partido->update([
            'resultado'  => null,
            'ganador_id' => null,
        ]);

        session()->flash('ok', 'Resultado anulado.');
    }

    // ── Reset helpers ──────────────────────────────────────────────────

    private function resetAsignar(): void
    {
        $this->reset(['modalAsignar', 'asignarGrupoId', 'asignarPosicion', 'asignarJugadorId', 'buscarJugador']);
    }

    private function resetResultado(): void
    {
        $this->reset(['modalResultado', 'partidoId', 'resultado', 'ganadorId']);
    }

    public function cerrarModales(): void
    {
        $this->resetAsignar();
        $this->resetResultado();
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

        // Tabla de posiciones por grupo
        $posiciones = [];
        foreach ($grupos as $grupo) {
            $posiciones[$grupo->id] = $grupo->calcularPosiciones();
        }

        // Jugadores inscriptos en este torneo/categoria disponibles para asignar
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

        // Partido activo para el modal de resultado
        $partidoActivo = $this->partidoId ? MasterPartido::with(['jugador1', 'jugador2'])->find($this->partidoId) : null;

        return view('livewire.master-detalle', compact(
            'grupos', 'posiciones', 'jugadoresDisponibles', 'jugadoresAsignados', 'partidoActivo'
        ))->layout('layouts.app');
    }
}
