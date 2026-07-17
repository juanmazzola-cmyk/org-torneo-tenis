<?php

namespace App\Livewire;

use App\Models\GaleriaFoto;
use Livewire\Component;

class AdminGaleria extends Component
{
    public function eliminar(int $id): void
    {
        $foto = GaleriaFoto::findOrFail($id);

        $path = public_path('images/galeria/' . $foto->filename);
        if (is_file($path)) {
            @unlink($path);
        }

        $foto->delete();

        session()->flash('galeria_ok', 'Foto eliminada.');
    }

    public function actualizarDescripcion(int $id, string $descripcion): void
    {
        GaleriaFoto::whereKey($id)->update(['descripcion' => $descripcion]);
    }

    public function render()
    {
        $fotos = GaleriaFoto::orderBy('orden')->orderBy('created_at')->get();

        return view('livewire.admin-galeria', compact('fotos'))
            ->layout('layouts.app');
    }
}
