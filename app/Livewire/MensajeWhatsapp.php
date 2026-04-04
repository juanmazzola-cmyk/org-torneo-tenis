<?php

namespace App\Livewire;

use App\Models\Jugador;
use Livewire\Component;

class MensajeWhatsapp extends Component
{
    public string $mensaje = '';
    public array $seleccionados = [];
    public bool $generado = false;

    public function seleccionarTodos(): void
    {
        $this->seleccionados = Jugador::whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->whereNot('apellido', 'Bye')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();
    }

    public function deseleccionarTodos(): void
    {
        $this->seleccionados = [];
    }

    public function generar(): void
    {
        $this->validate([
            'mensaje'       => 'required|min:3',
            'seleccionados' => 'required|array|min:1',
        ], [
            'mensaje.required'       => 'Escribí el mensaje.',
            'mensaje.min'            => 'El mensaje es muy corto.',
            'seleccionados.required' => 'Seleccioná al menos un jugador.',
            'seleccionados.min'      => 'Seleccioná al menos un jugador.',
        ]);

        $this->generado = true;
    }

    public function limpiar(): void
    {
        $this->reset(['mensaje', 'seleccionados', 'generado']);
    }

    private function formatearTelefono(string $telefono): string
    {
        // Dejar solo dígitos
        $tel = preg_replace('/\D/', '', $telefono);

        // Si empieza con 0, quitarlo y agregar código de Argentina (54)
        if (str_starts_with($tel, '0')) {
            $tel = '54' . ltrim($tel, '0');
        }

        // Si no tiene código de país (menos de 11 dígitos), agregar 54
        if (strlen($tel) <= 10) {
            $tel = '54' . $tel;
        }

        return $tel;
    }

    public function render()
    {
        $jugadores = Jugador::whereNot('apellido', 'Bye')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        $links = [];
        if ($this->generado && !empty($this->seleccionados)) {
            $jugadoresSeleccionados = Jugador::whereIn('id', $this->seleccionados)
                ->whereNotNull('telefono')
                ->where('telefono', '!=', '')
                ->get();

            foreach ($jugadoresSeleccionados as $j) {
                $links[] = [
                    'nombre' => $j->apellido . ', ' . $j->nombre,
                    'url'    => 'https://wa.me/' . $this->formatearTelefono($j->telefono)
                             . '?text=' . rawurlencode($this->mensaje),
                ];
            }
        }

        return view('livewire.mensaje-whatsapp', compact('jugadores', 'links'))
            ->layout('layouts.app');
    }
}
