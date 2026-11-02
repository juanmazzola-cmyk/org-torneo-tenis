<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Ranking;
use Livewire\Component;
use Livewire\Attributes\Rule;

class Categorias extends Component
{
    #[Rule('required|max:50|unique:categorias,nombre')]
    public string $nombre = '';
    public bool $sin_ranking = false;
    public bool $es_dobles = false;

    public ?int $editandoId = null;
    public bool $confirmandoEliminar = false;
    public ?int $eliminarId = null;

    public function guardar(): void
    {
        $data = [
            'nombre'      => $this->nombre,
            'sin_ranking' => $this->sin_ranking,
            'es_dobles'   => $this->es_dobles,
        ];

        if ($this->editandoId) {
            $this->validate([
                'nombre' => 'required|max:50|unique:categorias,nombre,' . $this->editandoId,
            ]);

            // Con puntos ya cargados, "Sin ranking" los escondería del Ranking sin borrarlos
            $filasRanking = Ranking::where('categoria_id', $this->editandoId)->count();
            if ($this->sin_ranking && $filasRanking > 0) {
                $this->addError('sin_ranking', "No se puede marcar \"Sin ranking\": la categoría ya tiene {$filasRanking} fila(s) de puntos en el ranking. No se guardó ningún cambio.");
                return;
            }

            Categoria::find($this->editandoId)->update($data);
            session()->flash('ok', 'Categoría actualizada.');
        } else {
            $this->validate();
            Categoria::create($data);
            session()->flash('ok', 'Categoría creada.');
        }
        $this->reset(['nombre', 'sin_ranking', 'es_dobles', 'editandoId']);
    }

    public function editar(int $id): void
    {
        $cat = Categoria::findOrFail($id);
        $this->editandoId  = $id;
        $this->nombre      = $cat->nombre;
        $this->sin_ranking = (bool) $cat->sin_ranking;
        $this->es_dobles   = (bool) $cat->es_dobles;
        $this->resetErrorBag();
    }

    public function confirmarEliminar(int $id): void
    {
        $this->eliminarId = $id;
        $this->confirmandoEliminar = true;
    }

    public function eliminar(): void
    {
        Categoria::findOrFail($this->eliminarId)->delete();
        session()->flash('ok', 'Categoría eliminada.');
        $this->reset(['confirmandoEliminar', 'eliminarId']);
    }

    public function cancelar(): void
    {
        $this->reset(['nombre', 'sin_ranking', 'es_dobles', 'editandoId', 'confirmandoEliminar', 'eliminarId']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.categorias', [
            'categorias' => Categoria::orderBy('nombre')->get(),
        ])->layout('layouts.app');
    }
}
