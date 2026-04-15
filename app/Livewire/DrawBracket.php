<?php

namespace App\Livewire;

use App\Models\Draw;
use App\Models\Jugador;
use App\Models\Partido;
use App\Models\Torneo;
use App\Models\Ranking;
use App\Models\Inscripcion;
use Livewire\Component;

class DrawBracket extends Component
{
    public Draw $draw;
    public Torneo $torneo;

    // Formulario resultado
    public ?int $partidoId = null;
    public string $ganadorId = '';
    public string $resultado = '';
    public bool $esWO = false;

    // Sets individuales
    public string $s1j1 = '', $s1j2 = '';
    public string $s2j1 = '', $s2j2 = '';
    public string $s3j1 = '', $s3j2 = '';

    // Asignar jugador a posición de primera ronda
    public ?int $asignandoPartidoId  = null;
    public string $asignandoPosicion = '';   // '1' o '2'
    public string $jugadorAsignarId  = '';

    public function mount(Torneo $torneo, Draw $draw): void
    {
        $this->torneo = $torneo;
        $this->draw   = $draw;
    }

    public function abrirResultado(int $partidoId): void
    {
        $partido = Partido::findOrFail($partidoId);
        $this->partidoId = $partidoId;
        $this->ganadorId = (string) ($partido->ganador_id ?? '');
        $this->esWO      = $partido->resultado === 'W.O.';

        if (!$this->esWO) {
            $this->resultado = $partido->resultado ?? '';
            $this->parsearSets($this->resultado);
            // Si el ganador era jugador2, los sets almacenados están con el ganador primero,
            // entonces invertimos los campos para que cada columna muestre los games reales del jugador.
            if ($partido->ganador_id && (string)$partido->jugador2_id === (string)$partido->ganador_id) {
                [$this->s1j1, $this->s1j2] = [$this->s1j2, $this->s1j1];
                [$this->s2j1, $this->s2j2] = [$this->s2j2, $this->s2j1];
                [$this->s3j1, $this->s3j2] = [$this->s3j2, $this->s3j1];
            }
        } else {
            $this->resultado = '';
        }
    }

    public function guardarResultado(): void
    {
        $partido  = Partido::findOrFail($this->partidoId);
        $flipSets = !$this->esWO && (string)$partido->jugador2_id === (string)$this->ganadorId;
        $this->resultado = $this->buildResultado($flipSets);

        $this->validate([
            'ganadorId' => 'required|exists:jugadores,id',
            'resultado' => 'nullable|max:100',
        ]);

        // Si ya tenía resultado anterior, limpiar estado previo
        if ($partido->ganador_id) {
            $this->limpiarResultadoPrevio($partido);
        }

        $perdedorId = $partido->jugador1_id == $this->ganadorId
            ? $partido->jugador2_id
            : $partido->jugador1_id;

        $partido->update([
            'ganador_id' => $this->ganadorId,
            'resultado'  => $this->resultado ?: null,
        ]);

        // Promover al ganador a la siguiente ronda
        $this->promoverGanador($partido, (int)$this->ganadorId);

        // Registrar al perdedor en el ranking
        if ($perdedorId) {
            $this->registrarRanking((int)$perdedorId, $partido->ronda);
        }

        // Si es la final, registrar también al campeón
        if ($partido->ronda === 1) {
            $puntosCampeon = (int) \App\Models\Config::get('puntos_campeon', 150);
            Ranking::updateOrCreate(
                [
                    'torneo_id'    => $this->torneo->id,
                    'jugador_id'   => (int) $this->ganadorId,
                    'categoria_id' => $this->draw->categoria_id,
                ],
                ['ronda_eliminado' => 0, 'puntos' => $puntosCampeon]
            );
        }

        session()->flash('ok', 'Resultado cargado.');
        $this->reset(['partidoId', 'ganadorId', 'resultado', 'esWO']);
    }

    /**
     * Limpia el ranking y el slot del próximo partido cuando se rectifica un resultado.
     * Sigue la cadena de victorias del viejo ganador hasta donde tenga resultados.
     */
    private function limpiarResultadoPrevio(Partido $partido): void
    {
        // Borrar ranking de ambos jugadores de este partido
        foreach ([$partido->jugador1_id, $partido->jugador2_id] as $jid) {
            if ($jid) {
                Ranking::where([
                    'torneo_id'    => $this->torneo->id,
                    'jugador_id'   => (int) $jid,
                    'categoria_id' => $this->draw->categoria_id,
                ])->delete();
            }
        }

        // Seguir la cadena hacia la final para limpiar slots y resultados derivados
        $viejoGanadorId = (int) $partido->ganador_id;
        $partidoActual  = $partido;

        while ($partidoActual->ronda > 1 && $viejoGanadorId) {
            $sigRonda    = $partidoActual->ronda / 2;
            $sigPosicion = (int) ceil($partidoActual->posicion / 2);
            $esJugador1  = $partidoActual->posicion % 2 !== 0;

            $siguiente = Partido::where('draw_id', $this->draw->id)
                ->where('ronda', $sigRonda)
                ->where('posicion', $sigPosicion)
                ->first();

            if (!$siguiente) break;

            $campo = $esJugador1 ? 'jugador1_id' : 'jugador2_id';

            // Solo intervenir si este slot tiene al viejo ganador
            if ((int) $siguiente->$campo !== $viejoGanadorId) break;

            $updates = [$campo => null];

            if ($siguiente->ganador_id) {
                // El próximo partido también tiene resultado — limpiarlo en cascada
                $perdedorSig = $siguiente->jugador1_id == $siguiente->ganador_id
                    ? $siguiente->jugador2_id
                    : $siguiente->jugador1_id;

                if ($perdedorSig) {
                    Ranking::where([
                        'torneo_id'    => $this->torneo->id,
                        'jugador_id'   => (int) $perdedorSig,
                        'categoria_id' => $this->draw->categoria_id,
                    ])->delete();
                }

                // Si era la final, borrar también el ranking del campeón
                if ($siguiente->ronda === 1) {
                    Ranking::where([
                        'torneo_id'    => $this->torneo->id,
                        'jugador_id'   => (int) $siguiente->ganador_id,
                        'categoria_id' => $this->draw->categoria_id,
                    ])->delete();
                }

                $viejoGanadorId = (int) $siguiente->ganador_id;
                $updates['ganador_id'] = null;
                $updates['resultado']  = null;
            } else {
                $viejoGanadorId = 0; // no hay más resultados que limpiar
            }

            $siguiente->update($updates);
            $partidoActual = $siguiente;

            if (!isset($updates['ganador_id'])) break;
        }
    }

    private function promoverGanador(Partido $partido, int $ganadorId): void
    {
        if ($partido->ronda <= 1) return; // Ya es la final

        $siguienteRonda    = $partido->ronda / 2;
        $siguientePosicion = (int) ceil($partido->posicion / 2);
        $esJugador1        = $partido->posicion % 2 !== 0;

        $siguientePartido = Partido::where('draw_id', $this->draw->id)
            ->where('ronda', $siguienteRonda)
            ->where('posicion', $siguientePosicion)
            ->first();

        if ($siguientePartido) {
            if ($esJugador1) {
                $siguientePartido->update(['jugador1_id' => $ganadorId]);
            } else {
                $siguientePartido->update(['jugador2_id' => $ganadorId]);
            }
        }
    }

    private function registrarRanking(int $jugadorId, int $ronda): void
    {
        // No registrar puntos para el jugador Bye
        $byeJugador = Jugador::where('apellido', 'Bye')->where('nombre', 'Bye')->first();
        if ($byeJugador && $jugadorId === $byeJugador->id) return;

        $puntos = Partido::puntosSegunRonda($ronda, (int)$this->draw->tamano);

        Ranking::updateOrCreate(
            [
                'torneo_id'   => $this->torneo->id,
                'jugador_id'  => $jugadorId,
                'categoria_id'=> $this->draw->categoria_id,
            ],
            [
                'ronda_eliminado' => $ronda,
                'puntos'          => $puntos,
            ]
        );
    }

    public function cancelarResultado(): void
    {
        $this->reset(['partidoId', 'ganadorId', 'resultado', 'esWO',
            's1j1', 's1j2', 's2j1', 's2j2', 's3j1', 's3j2']);
    }

    public function anularResultado(int $partidoId): void
    {
        $partido = Partido::findOrFail($partidoId);

        if (!$partido->ganador_id) return;

        $this->limpiarResultadoPrevio($partido);

        $partido->update(['ganador_id' => null, 'resultado' => null]);

        session()->flash('ok', 'Resultado anulado.');
    }

    public function recalcularRanking(): void
    {
        // Borrar todo el ranking de esta categoría en este torneo
        Ranking::where([
            'torneo_id'    => $this->torneo->id,
            'categoria_id' => $this->draw->categoria_id,
        ])->delete();

        // Reconstruir desde los resultados actuales del draw
        $partidos = Partido::where('draw_id', $this->draw->id)
            ->whereNotNull('ganador_id')
            ->get();

        $byeJugador = Jugador::where('apellido', 'Bye')->where('nombre', 'Bye')->first();
        $byeId      = $byeJugador?->id;

        foreach ($partidos as $partido) {
            $perdedorId = $partido->jugador1_id == $partido->ganador_id
                ? $partido->jugador2_id
                : $partido->jugador1_id;

            if ($perdedorId && (!$byeId || (int)$perdedorId !== $byeId)) {
                $puntos = Partido::puntosSegunRonda((int)$partido->ronda, (int)$this->draw->tamano);
                Ranking::updateOrCreate(
                    [
                        'torneo_id'    => $this->torneo->id,
                        'jugador_id'   => (int) $perdedorId,
                        'categoria_id' => $this->draw->categoria_id,
                    ],
                    ['ronda_eliminado' => $partido->ronda, 'puntos' => $puntos]
                );
            }

            // Si es la final, registrar al campeón
            if ($partido->ronda === 1) {
                $puntosCampeon = (int) \App\Models\Config::get('puntos_campeon', 150);
                Ranking::updateOrCreate(
                    [
                        'torneo_id'    => $this->torneo->id,
                        'jugador_id'   => (int) $partido->ganador_id,
                        'categoria_id' => $this->draw->categoria_id,
                    ],
                    ['ronda_eliminado' => 0, 'puntos' => $puntosCampeon]
                );
            }
        }

        session()->flash('ok', 'Ranking recalculado correctamente.');
    }

    // ── Asignar jugador a posición de primera ronda ────────────────────
    public function abrirAsignar(int $partidoId, string $posicion): void
    {
        $partido = Partido::findOrFail($partidoId);
        $this->asignandoPartidoId = $partidoId;
        $this->asignandoPosicion  = $posicion;
        $this->jugadorAsignarId   = (string) ($posicion === '1'
            ? ($partido->jugador1_id ?? '')
            : ($partido->jugador2_id ?? ''));
    }

    public function guardarAsignacion(): void
    {
        $this->validate([
            'jugadorAsignarId' => 'required|exists:jugadores,id',
        ], ['jugadorAsignarId.required' => 'Seleccioná un jugador.']);

        $byeJugador   = Jugador::where('apellido', 'Bye')->where('nombre', 'Bye')->first();
        $byeId        = $byeJugador?->id;

        // Si el jugador ya está en otra posición de primera ronda, liberarlo
        // (excepto Bye, que puede estar en múltiples posiciones)
        if (!$byeId || (int)$this->jugadorAsignarId !== $byeId) {
            $primeraRonda = Partido::where('draw_id', $this->draw->id)->max('ronda');
            Partido::where('draw_id', $this->draw->id)
                ->where('ronda', $primeraRonda)
                ->where('id', '!=', $this->asignandoPartidoId)
                ->each(function ($p) {
                    if ((string)$p->jugador1_id === (string)$this->jugadorAsignarId) {
                        $p->update(['jugador1_id' => null]);
                    }
                    if ((string)$p->jugador2_id === (string)$this->jugadorAsignarId) {
                        $p->update(['jugador2_id' => null]);
                    }
                });
        }

        $campo = $this->asignandoPosicion === '1' ? 'jugador1_id' : 'jugador2_id';
        $partido = Partido::findOrFail($this->asignandoPartidoId);
        $partido->update([$campo => $this->jugadorAsignarId]);
        $partido->refresh();

        // Auto-avanzar si hay un Bye enfrentado a un jugador real
        if ($byeId && !$partido->ganador_id) {
            $j1IsBye = $partido->jugador1_id == $byeId;
            $j2IsBye = $partido->jugador2_id == $byeId;
            if ($j1IsBye && $partido->jugador2_id) {
                $ganadorId = (int) $partido->jugador2_id;
                $partido->update(['ganador_id' => $ganadorId, 'resultado' => 'Bye']);
                $this->promoverGanador($partido, $ganadorId);
            } elseif ($j2IsBye && $partido->jugador1_id) {
                $ganadorId = (int) $partido->jugador1_id;
                $partido->update(['ganador_id' => $ganadorId, 'resultado' => 'Bye']);
                $this->promoverGanador($partido, $ganadorId);
            }
        }

        session()->flash('ok', 'Jugador asignado.');
        $this->reset(['asignandoPartidoId', 'asignandoPosicion', 'jugadorAsignarId']);
    }

    public function quitarJugador(int $partidoId, string $posicion): void
    {
        $campo   = $posicion === '1' ? 'jugador1_id' : 'jugador2_id';
        $partido = Partido::findOrFail($partidoId);

        // Si hay resultado (ej. auto-avance por Bye), limpiarlo primero
        if ($partido->ganador_id) {
            $this->limpiarResultadoPrevio($partido);
            $partido->refresh();
            $partido->update(['ganador_id' => null, 'resultado' => null]);
        }

        $partido->update([$campo => null]);
        session()->flash('ok', 'Jugador quitado del cuadro.');
    }

    public function cancelarAsignacion(): void
    {
        $this->reset(['asignandoPartidoId', 'asignandoPosicion', 'jugadorAsignarId']);
    }

    // ── Helpers sets ──────────────────────────────────────────────────

    private function parsearSets(string $resultado): void
    {
        $this->s1j1 = $this->s1j2 = '';
        $this->s2j1 = $this->s2j2 = '';
        $this->s3j1 = $this->s3j2 = '';

        if (!$resultado) return;

        foreach (explode(' ', trim($resultado)) as $i => $set) {
            if (!preg_match('/^(\d+)-(\d+)$/', $set, $m)) continue;
            match($i) {
                0 => [$this->s1j1, $this->s1j2] = [$m[1], $m[2]],
                1 => [$this->s2j1, $this->s2j2] = [$m[1], $m[2]],
                2 => [$this->s3j1, $this->s3j2] = [$m[1], $m[2]],
                default => null,
            };
        }
    }

    private function buildResultado(bool $flipSets = false): string
    {
        if ($this->esWO) return 'W.O.';
        $sets = [];
        if ($this->s1j1 !== '' && $this->s1j2 !== '') {
            $sets[] = $flipSets ? "{$this->s1j2}-{$this->s1j1}" : "{$this->s1j1}-{$this->s1j2}";
        }
        if ($this->s2j1 !== '' && $this->s2j2 !== '') {
            $sets[] = $flipSets ? "{$this->s2j2}-{$this->s2j1}" : "{$this->s2j1}-{$this->s2j2}";
        }
        if ($this->s3j1 !== '' && $this->s3j2 !== '') {
            $sets[] = $flipSets ? "{$this->s3j2}-{$this->s3j1}" : "{$this->s3j1}-{$this->s3j2}";
        }
        return implode(' ', $sets);
    }

    public function render()
    {
        $partidos = Partido::with(['jugador1', 'jugador2', 'ganador'])
            ->where('draw_id', $this->draw->id)
            ->orderBy('ronda', 'desc')
            ->orderBy('posicion')
            ->get()
            ->groupBy('ronda');

        // Todos los inscriptos en esta categoría (para poder reasignar libremente)
        $inscriptos = Inscripcion::with('jugador')
            ->where('torneo_id', $this->torneo->id)
            ->where('categoria_id', $this->draw->categoria_id)
            ->get()
            ->sortBy('jugador.apellido');

        // Ronda más alta = primera ronda del draw (comparación numérica)
        $primeraRonda = $partidos->keys()->map(fn($k) => (int)$k)->max();

        $byeJugador = Jugador::where('apellido', 'Bye')->where('nombre', 'Bye')->first();

        return view('livewire.draw-bracket', compact('partidos', 'inscriptos', 'primeraRonda', 'byeJugador'))
            ->layout('layouts.app');
    }
}
