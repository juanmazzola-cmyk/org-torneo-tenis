<?php

namespace App\Livewire;

use App\Models\Torneo;
use Livewire\Component;

class Torneos extends Component
{
    public string $nombre = '';
    public string $fecha_inicio = '';
    public string $fecha_fin = '';
    public string $estado = 'activo';

    public ?int $editandoId = null;
    public bool $confirmandoEliminar = false;
    public ?int $eliminarId = null;

    protected function rules(): array
    {
        return [
            'nombre'       => 'required|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'estado'       => 'required|in:activo,finalizado,cancelado',
        ];
    }

    public function guardar(): void
    {
        $this->validate();
        $data = [
            'nombre'       => $this->nombre,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin'    => $this->fecha_fin ?: null,
            'estado'       => $this->estado,
        ];

        if ($this->editandoId) {
            Torneo::find($this->editandoId)->update($data);
            session()->flash('ok', 'Torneo actualizado.');
        } else {
            Torneo::create($data);
            session()->flash('ok', 'Torneo creado.');
        }
        $this->resetFormulario();
    }

    public function editar(int $id): void
    {
        $t = Torneo::findOrFail($id);
        $this->editandoId   = $id;
        $this->nombre       = $t->nombre;
        $this->fecha_inicio = $t->fecha_inicio->format('Y-m-d');
        $this->fecha_fin    = $t->fecha_fin ? $t->fecha_fin->format('Y-m-d') : '';
        $this->estado       = $t->estado;
    }

    public function confirmarEliminar(int $id): void
    {
        $this->eliminarId = $id;
        $this->confirmandoEliminar = true;
    }

    public function eliminar(): void
    {
        Torneo::findOrFail($this->eliminarId)->delete();
        session()->flash('ok', 'Torneo eliminado.');
        $this->reset(['confirmandoEliminar', 'eliminarId']);
    }

    public function cancelar(): void
    {
        $this->reset(['confirmandoEliminar', 'eliminarId']);
        $this->resetFormulario();
    }

    private function resetFormulario(): void
    {
        $this->reset(['editandoId', 'nombre', 'fecha_inicio', 'fecha_fin']);
        $this->estado = 'activo';
    }

    public function render()
    {
        return view('livewire.torneos', [
            'torneos' => Torneo::withCount('inscripciones')->orderByDesc('fecha_inicio')->get(),
        ])->layout('layouts.app');
    }
}
