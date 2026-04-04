<?php

namespace App\Livewire;

use App\Models\Categoria;
use Livewire\Component;
use Livewire\Attributes\Rule;

class Categorias extends Component
{
    #[Rule('required|max:50|unique:categorias,nombre')]
    public string $nombre = '';

    public ?int $editandoId = null;
    public bool $confirmandoEliminar = false;
    public ?int $eliminarId = null;

    public function guardar(): void
    {
        if ($this->editandoId) {
            $this->validate([
                'nombre' => 'required|max:50|unique:categorias,nombre,' . $this->editandoId,
            ]);
            Categoria::find($this->editandoId)->update(['nombre' => $this->nombre]);
            session()->flash('ok', 'Categoría actualizada.');
        } else {
            $this->validate();
            Categoria::create(['nombre' => $this->nombre]);
            session()->flash('ok', 'Categoría creada.');
        }
        $this->reset(['nombre', 'editandoId']);
    }

    public function editar(int $id): void
    {
        $cat = Categoria::findOrFail($id);
        $this->editandoId = $id;
        $this->nombre = $cat->nombre;
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
        $this->reset(['nombre', 'editandoId', 'confirmandoEliminar', 'eliminarId']);
    }

    public function render()
    {
        return view('livewire.categorias', [
            'categorias' => Categoria::orderBy('nombre')->get(),
        ])->layout('layouts.app');
    }
}
