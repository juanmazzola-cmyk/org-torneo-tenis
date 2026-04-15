<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Torneo;
use App\Services\RankingService;
use App\Exports\RankingExport;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class RankingLista extends Component
{
    public string $filtroTorneo    = '';
    public string $filtroCategoria = '';
    public string $filtroAnio      = '';

    public function mount(): void
    {
        $this->filtroAnio = (string) now()->year;
    }

    public function exportar()
    {
        return Excel::download(
            new RankingExport(
                $this->filtroTorneo    ?: null,
                $this->filtroCategoria ?: null,
                $this->filtroAnio      ? (int)$this->filtroAnio : null
            ),
            'ranking.xlsx'
        );
    }

    public function render()
    {
        $categoriasData = RankingService::calcular(
            $this->filtroCategoria ? (int)$this->filtroCategoria : null,
            $this->filtroTorneo    ? (int)$this->filtroTorneo    : null,
            $this->filtroAnio      ? (int)$this->filtroAnio      : null
        );

        $anos = Torneo::whereNotNull('fecha_inicio')
            ->get()
            ->map(fn($t) => \Carbon\Carbon::parse($t->fecha_inicio)->year)
            ->unique()->sortDesc()->values();

        return view('livewire.ranking-lista', [
            'categoriasData' => $categoriasData,
            'torneos'        => Torneo::orderByDesc('fecha_inicio')->get(),
            'categorias'     => Categoria::orderBy('nombre')->get(),
            'anos'           => $anos,
        ])->layout('layouts.app');
    }
}
