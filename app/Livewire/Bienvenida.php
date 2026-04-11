<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Config;
use App\Models\Jugador;
use App\Models\MasterFinal;
use App\Models\MasterPartido;
use App\Models\Partido;
use App\Models\Torneo;
use App\Services\RankingService;
use Livewire\Component;

class Bienvenida extends Component
{
    public string $panel = '';           // '' | 'ranking' | 'torneos' | 'misPartidos'
    public string $filtroCategoria = '';
    public string $iframeUrl = '';       // URL cargada en el iframe overlay
    public string $busqueda = '';        // búsqueda de jugador en "Mis Partidos"
    public ?int   $jugadorId = null;     // jugador seleccionado en "Mis Partidos"

    public function abrirRanking(): void
    {
        $this->panel = 'ranking';
    }

    public function abrirTorneos(): void
    {
        $this->panel = 'torneos';
    }

    public function abrirMisPartidos(): void
    {
        $this->panel       = 'misPartidos';
        $this->jugadorId   = null;
        $this->busqueda    = '';
    }

    public function abrirIframe(string $url): void
    {
        $this->iframeUrl = $url;
    }

    public function cerrarIframe(): void
    {
        $this->iframeUrl = '';
    }

    public function cerrar(): void
    {
        $this->panel         = '';
        $this->iframeUrl     = '';
        $this->filtroCategoria = '';
        $this->jugadorId     = null;
        $this->busqueda      = '';
    }

    public function seleccionarJugador(int $id): void
    {
        $this->jugadorId = $id;
        $this->busqueda  = '';
    }

    private function nombreRonda(int $ronda): string
    {
        return match($ronda) {
            32      => '32avos',
            16      => '16avos',
            8       => 'Octavos',
            4       => 'Cuartos',
            2       => 'Semifinal',
            1       => 'Final',
            default => 'Ronda ' . $ronda,
        };
    }

    public function render()
    {
        $torneos = Torneo::with([
            'draws.categoria',
            'draws.partidos' => fn($q) => $q->where('ronda', 1)->whereNotNull('ganador_id'),
            'masters.categoria',
        ])->where('estado', 'activo')->orderBy('fecha_inicio', 'desc')->get();

        $clubNombre = Config::get('club_nombre', 'Club de Tenis');
        $clubCiudad = Config::get('club_ciudad', '');

        $categoriasData = [];
        $categorias     = collect();

        if ($this->panel === 'ranking') {
            $categoriasData = RankingService::calcular(
                $this->filtroCategoria ? (int) $this->filtroCategoria : null
            );
            $categorias = Categoria::orderBy('nombre')->get();
        }

        $torneosFinalizados = collect();
        if ($this->panel === 'torneos') {
            $torneosFinalizados = Torneo::with([
                'draws.categoria',
                'draws.partidos' => fn($q) => $q->where('ronda', 1)->whereNotNull('ganador_id'),
                'masters.categoria',
            ])
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->filter(fn($t) =>
                $t->draws->contains(fn($d) => $d->partidos->isNotEmpty()) ||
                $t->masters->contains(fn($m) => $m->estado === 'finalizado')
            );
        }

        // Mis Partidos
        $jugadores          = collect();
        $jugadorSeleccionado = null;
        $misPartidosPorAnio  = [];

        if ($this->panel === 'misPartidos') {
            $query = Jugador::orderBy('apellido')->orderBy('nombre');
            if (strlen($this->busqueda) >= 2) {
                $b = $this->busqueda;
                $query->where(function($q) use ($b) {
                    $q->where('apellido', 'like', "%{$b}%")
                      ->orWhere('nombre', 'like', "%{$b}%");
                });
            }
            $jugadores = $query->get();

            if ($this->jugadorId) {
                $jugadorSeleccionado = Jugador::find($this->jugadorId);
                $id = $this->jugadorId;

                // Partidos de draw
                $drawPartidos = Partido::with(['draw.torneo', 'draw.categoria', 'jugador1', 'jugador2'])
                    ->where(function($q) use ($id) {
                        $q->where('jugador1_id', $id)->orWhere('jugador2_id', $id);
                    })
                    ->whereNotNull('ganador_id')
                    ->whereHas('draw.torneo')
                    ->get();

                // Partidos de master (zona)
                $masterPartidos = MasterPartido::with([
                    'grupo.master.torneo', 'grupo.master.categoria', 'jugador1', 'jugador2',
                ])
                    ->where(function($q) use ($id) {
                        $q->where('jugador1_id', $id)->orWhere('jugador2_id', $id);
                    })
                    ->whereNotNull('ganador_id')
                    ->whereHas('grupo.master.torneo')
                    ->get();

                // Fase final de master
                $masterFinales = MasterFinal::with([
                    'master.torneo', 'master.categoria', 'jugador1', 'jugador2',
                ])
                    ->where(function($q) use ($id) {
                        $q->where('jugador1_id', $id)->orWhere('jugador2_id', $id);
                    })
                    ->whereNotNull('ganador_id')
                    ->whereHas('master.torneo')
                    ->get();

                $todos = collect();

                foreach ($drawPartidos as $p) {
                    $anio = $p->draw->torneo->fecha_inicio?->year
                          ?? $p->created_at->year;
                    $todos->push([
                        'anio'      => $anio,
                        'torneo'    => $p->draw->torneo->nombre,
                        'categoria' => $p->draw->categoria->nombre,
                        'ronda'     => $this->nombreRonda($p->ronda),
                        'rival'     => $p->jugador1_id == $id ? $p->jugador2 : $p->jugador1,
                        'resultado' => $p->resultado,
                        'gano'      => $p->ganador_id == $id,
                    ]);
                }

                foreach ($masterPartidos as $p) {
                    $anio = $p->grupo->master->torneo->fecha_inicio?->year
                          ?? $p->created_at->year;
                    $todos->push([
                        'anio'      => $anio,
                        'torneo'    => $p->grupo->master->torneo->nombre,
                        'categoria' => $p->grupo->master->categoria->nombre,
                        'ronda'     => 'Zona – Jornada ' . $p->jornada,
                        'rival'     => $p->jugador1_id == $id ? $p->jugador2 : $p->jugador1,
                        'resultado' => $p->resultado,
                        'gano'      => $p->ganador_id == $id,
                    ]);
                }

                foreach ($masterFinales as $p) {
                    $anio = $p->master->torneo->fecha_inicio?->year
                          ?? $p->created_at->year;
                    $todos->push([
                        'anio'      => $anio,
                        'torneo'    => $p->master->torneo->nombre,
                        'categoria' => $p->master->categoria->nombre,
                        'ronda'     => $p->label(),
                        'rival'     => $p->jugador1_id == $id ? $p->jugador2 : $p->jugador1,
                        'resultado' => $p->resultado,
                        'gano'      => $p->ganador_id == $id,
                    ]);
                }

                $misPartidosPorAnio = $todos->sortByDesc('anio')
                    ->groupBy('anio')
                    ->all();
            }
        }

        return view('livewire.bienvenida', compact(
            'torneos', 'clubNombre', 'clubCiudad',
            'categoriasData', 'categorias', 'torneosFinalizados',
            'jugadores', 'jugadorSeleccionado', 'misPartidosPorAnio'
        ))->layout('layouts.publica');
    }
}
