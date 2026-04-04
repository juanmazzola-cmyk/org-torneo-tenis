<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Services\RankingService;
use Livewire\Component;

class RankingPublico extends Component
{
    public string $filtroCategoria = '';

    public function render()
    {
        return view('livewire.ranking-publico', [
            'categoriasData' => RankingService::calcular(
                $this->filtroCategoria ? (int)$this->filtroCategoria : null
            ),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ])->layout('layouts.publica');
    }
}
