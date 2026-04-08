<?php

namespace App\Livewire;

use App\Models\Master;
use App\Models\MasterFinal;
use App\Models\Torneo;
use Livewire\Component;

class MasterPublico extends Component
{
    public Torneo $torneo;
    public Master $master;

    public function mount(Torneo $torneo, Master $master): void
    {
        $this->torneo = $torneo;
        $this->master = $master;
    }

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

        $faseFinal = MasterFinal::with(['jugador1', 'jugador2', 'ganador'])
            ->where('master_id', $this->master->id)
            ->orderByRaw("FIELD(tipo, 'semifinal_1', 'semifinal_2', 'final')")
            ->get();

        return view('livewire.master-publico', compact('grupos', 'posiciones', 'faseFinal'))
            ->layout('layouts.publica');
    }
}
