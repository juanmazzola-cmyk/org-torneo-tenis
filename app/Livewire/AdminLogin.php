<?php

namespace App\Livewire;

use App\Models\Config;
use Livewire\Component;

class AdminLogin extends Component
{
    public string $codigo = '';

    public function ingresar(): void
    {
        $this->validate([
            'codigo' => 'required|min:1',
        ], [
            'codigo.required' => 'Ingresá el código de acceso.',
        ]);

        $codigoCorrecto = Config::get('admin_code', 'ADMIN1');

        if ($this->codigo === $codigoCorrecto) {
            session(['admin_auth' => true]);
            redirect()->route('torneos');
            return;
        }

        $this->addError('codigo', 'Código incorrecto.');
        $this->codigo = '';
    }

    public function render()
    {
        return view('livewire.admin-login')->layout('layouts.publica');
    }
}
