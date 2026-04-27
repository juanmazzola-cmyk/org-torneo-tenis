<?php

namespace App\Livewire;

use App\Models\Draw;
use App\Models\Partido;
use App\Models\Torneo;
use Livewire\Component;

class ResultadosPublico extends Component
{
    public Torneo $torneo;
    public Draw   $draw;

    public function mount(Torneo $torneo, Draw $draw): void
    {
        $this->torneo = $torneo;
        $this->draw   = $draw;
    }

    public function render()
    {
        // Solo partidos con resultado cargado, del más avanzado (ronda 1 = final) al inicial
        $partidos = Partido::with(['jugador1', 'jugador2'])
            ->where('draw_id', $this->draw->id)
            ->whereNotNull('ganador_id')
            ->where('resultado', '!=', 'Bye')
            ->whereNotNull('resultado')
            ->orderBy('ronda')
            ->orderBy('posicion')
            ->get()
            ->groupBy(fn($p) => (int) $p->ronda);

        $nombreRonda = function (int $ronda, int $tamano): string {
            return match($ronda) {
                32      => '32avos de Final',
                16      => '16avos de Final',
                8       => 'Octavos de Final',
                4       => 'Cuartos de Final',
                2       => 'Semifinal',
                1       => 'Final',
                default => 'Ronda ' . $ronda,
            };
        };

        return view('livewire.resultados-publico', compact('partidos', 'nombreRonda'))
            ->layout('layouts.publica');
    }
}
