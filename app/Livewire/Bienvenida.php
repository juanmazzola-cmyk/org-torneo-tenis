<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Config;
use App\Models\Torneo;
use App\Services\RankingService;
use Livewire\Component;

class Bienvenida extends Component
{
    public string $panel = '';           // '' | 'ranking' | 'torneos'
    public string $filtroCategoria = '';

    public function abrirRanking(): void
    {
        $this->panel = 'ranking';
    }

    public function abrirTorneos(): void
    {
        $this->panel = 'torneos';
    }

    public function cerrar(): void
    {
        $this->panel = '';
        $this->filtroCategoria = '';
    }

    public function render()
    {
        $torneos = Torneo::with([
            'draws.categoria',
            'draws.partidos' => fn($q) => $q->where('ronda', 1)->whereNotNull('ganador_id'),
            'masters.categoria',
        ])->where('estado', 'activo')->orderBy('fecha_inicio', 'desc')->get();

        $clubNombre = Config::get('club_nombre', 'Club de Tenis');
        $clubCiudad = Config::get('club_ciudad', '');

        $categoriasData = [];
        $categorias     = collect();

        if ($this->panel === 'ranking') {
            $categoriasData = RankingService::calcular(
                $this->filtroCategoria ? (int) $this->filtroCategoria : null
            );
            $categorias = Categoria::orderBy('nombre')->get();
        }

        $torneosFinalizados = collect();
        if ($this->panel === 'torneos') {
            $torneosFinalizados = Torneo::with([
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
        }

        return view('livewire.bienvenida', compact(
            'torneos', 'clubNombre', 'clubCiudad',
            'categoriasData', 'categorias', 'torneosFinalizados'
        ))->layout('layouts.publica');
    }
}
