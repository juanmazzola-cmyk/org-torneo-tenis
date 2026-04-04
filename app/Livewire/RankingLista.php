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

    public function exportar()
    {
        return Excel::download(
            new RankingExport(
                $this->filtroTorneo    ?: null,
                $this->filtroCategoria ?: null
            ),
            'ranking.xlsx'
        );
    }

    public function render()
    {
        $categoriasData = RankingService::calcular(
            $this->filtroCategoria ? (int)$this->filtroCategoria : null,
            $this->filtroTorneo    ? (int)$this->filtroTorneo    : null
        );

        return view('livewire.ranking-lista', [
            'categoriasData' => $categoriasData,
            'torneos'        => Torneo::orderByDesc('fecha_inicio')->get(),
            'categorias'     => Categoria::orderBy('nombre')->get(),
        ])->layout('layouts.app');
    }
}
