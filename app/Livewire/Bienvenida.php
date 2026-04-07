<?php

namespace App\Livewire;

use App\Models\Config;
use App\Models\Torneo;
use Livewire\Component;

class Bienvenida extends Component
{
    public function render()
    {
        $torneos = Torneo::with([
            'draws.categoria',
            'draws.partidos' => fn($q) => $q->where('ronda', 1)->whereNotNull('ganador_id'),
        ])->orderBy('fecha_inicio', 'desc')->get()
          ->filter(fn($t) => ($t->tipo ?? 'normal') !== 'master')
          ->values();
        $clubNombre = Config::get('club_nombre', 'Club de Tenis');
        $clubCiudad = Config::get('club_ciudad', '');

        return view('livewire.bienvenida', compact('torneos', 'clubNombre', 'clubCiudad'))
            ->layout('layouts.publica');
    }
}
