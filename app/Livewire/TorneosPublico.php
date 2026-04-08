<?php

namespace App\Livewire;

use App\Models\Config;
use App\Models\Torneo;
use Livewire\Component;

class TorneosPublico extends Component
{
    public function render()
    {
        $torneos = Torneo::with([
            'draws.categoria',
            'draws.partidos' => fn($q) => $q->where('ronda', 1)->whereNotNull('ganador_id'),
            'masters.categoria',
        ])
        ->orderBy('fecha_inicio', 'desc')
        ->get()
        ->filter(fn($t) =>
            $t->draws->contains(fn($d) => $d->partidos->isNotEmpty()) ||
            $t->masters->contains(fn($m) => $m->estado === 'finalizado')
        );

        $clubNombre = Config::get('club_nombre', 'Club de Tenis');

        return view('livewire.torneos-publico', compact('torneos', 'clubNombre'))
            ->layout('layouts.publica');
    }
}
