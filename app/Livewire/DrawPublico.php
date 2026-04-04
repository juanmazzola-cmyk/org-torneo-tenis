<?php

namespace App\Livewire;

use App\Models\Draw;
use App\Models\Partido;
use App\Models\Torneo;
use Livewire\Component;

class DrawPublico extends Component
{
    public Draw $draw;
    public Torneo $torneo;

    public function mount(Torneo $torneo, Draw $draw): void
    {
        $this->torneo = $torneo;
        $this->draw   = $draw;
    }

    public function render()
    {
        $partidos = Partido::with(['jugador1', 'jugador2', 'ganador'])
            ->where('draw_id', $this->draw->id)
            ->orderBy('ronda', 'desc')
            ->orderBy('posicion')
            ->get()
            ->groupBy('ronda');

        $primeraRonda = $partidos->keys()->map(fn($k) => (int)$k)->max();

        return view('livewire.draw-publico', compact('partidos', 'primeraRonda'))
            ->layout('layouts.publica');
    }
}
