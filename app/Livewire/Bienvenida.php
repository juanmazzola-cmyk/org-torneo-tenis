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
use Livewire\Attributes\Url;
use Livewire\Component;

class Bienvenida extends Component
{
    #[Url(as: 'panel')]
    public string $panel = '';           // '' | 'ranking' | 'torneos' | 'misPartidos' | 'info'

    #[Url(as: 'cat')]
    public string $filtroCategoria = ''; // ranking

    #[Url(as: 'anio')]
    public string $filtroAnio = '';      // ranking + torneos

    #[Url(as: 'cattor')]
    public string $filtroCategoriaTorneos = ''; // torneos

    public string $busqueda    = '';      // búsqueda de jugador en "Mis Partidos"
    public ?int   $jugadorId  = null;    // jugador seleccionado en "Mis Partidos"
    public string $misAnio    = '';      // filtro año en "Mis Partidos"
    public ?int   $misTorneoId = null;   // filtro torneo en "Mis Partidos"

    public function abrirRanking(): void
    {
        $this->panel           = 'ranking';
        $this->filtroAnio      = (string) now()->year;
        $this->filtroCategoria = '';
    }

    public function abrirTorneos(): void
    {
        $this->panel                  = 'torneos';
        $this->filtroAnio             = (string) now()->year;
        $this->filtroCategoriaTorneos = '';
    }

    public function abrirMisPartidos(): void
    {
        $this->panel       = 'misPartidos';
        $this->jugadorId   = null;
        $this->busqueda    = '';
        $this->misAnio     = '';
        $this->misTorneoId = null;
    }

    public function abrirInfo(): void
    {
        $this->panel = 'info';
    }

    public function cerrar(): void
    {
        $this->panel                  = '';
        $this->filtroCategoria        = '';
        $this->filtroAnio             = '';
        $this->filtroCategoriaTorneos = '';
        $this->jugadorId              = null;
        $this->busqueda               = '';
        $this->misAnio                = '';
        $this->misTorneoId            = null;
    }

    public function seleccionarJugador(int $id): void
    {
        $this->jugadorId   = $id;
        $this->busqueda    = '';
        $this->misAnio     = '';
        $this->misTorneoId = null;
    }

    public function updatedMisAnio(): void
    {
        $this->misTorneoId = null;
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
        $panelInfo  = Config::get('panel_info', '');

        $categorias     = Categoria::orderBy('nombre')->get();
        $categoriasData = [];

        if ($this->panel === 'ranking') {
            $categoriasData = RankingService::calcular(
                $this->filtroCategoria ? (int) $this->filtroCategoria : null,
                null,
                $this->filtroAnio ? (int) $this->filtroAnio : null
            );
        }

        $torneosFinalizados = collect();
        if ($this->panel === 'torneos') {
            $catId = $this->filtroCategoriaTorneos ? (int) $this->filtroCategoriaTorneos : null;

            $torneosFinalizados = Torneo::with([
                'draws.categoria',
                'draws.partidos' => fn($q) => $q->where('ronda', 1)->whereNotNull('ganador_id'),
                'masters.categoria',
            ])
            ->when($this->filtroAnio, fn($q) => $q->whereYear('fecha_inicio', $this->filtroAnio))
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->filter(function ($t) use ($catId) {
                $draws   = $catId ? $t->draws->where('categoria_id', $catId)   : $t->draws;
                $masters = $catId ? $t->masters->where('categoria_id', $catId) : $t->masters;
                return $draws->contains(fn($d) => $d->partidos->isNotEmpty()) ||
                       $masters->contains(fn($m) => $m->estado === 'finalizado');
            });
        }

        // Mis Partidos
        $jugadores           = collect();
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

                $byeId = \App\Models\Jugador::where('apellido', 'Bye')->where('nombre', 'Bye')->value('id');

                $drawPartidos = Partido::with(['draw.torneo', 'draw.categoria', 'jugador1', 'jugador2'])
                    ->where(function($q) use ($id) {
                        $q->where('jugador1_id', $id)->orWhere('jugador2_id', $id);
                    })
                    ->whereNotNull('ganador_id')
                    ->whereHas('draw.torneo')
                    ->when($byeId, fn($q) =>
                        $q->where('jugador1_id', '!=', $byeId)->where('jugador2_id', '!=', $byeId)
                    )
                    ->get();

                $masterPartidos = MasterPartido::with([
                    'grupo.master.torneo', 'grupo.master.categoria', 'jugador1', 'jugador2',
                ])
                    ->where(function($q) use ($id) {
                        $q->where('jugador1_id', $id)->orWhere('jugador2_id', $id);
                    })
                    ->whereNotNull('ganador_id')
                    ->whereHas('grupo.master.torneo')
                    ->get();

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
                    $anio = $p->draw->torneo->fecha_inicio?->year ?? $p->created_at->year;
                    $todos->push([
                        'anio'       => $anio,
                        'torneo_id'  => $p->draw->torneo->id,
                        'torneo'     => $p->draw->torneo->nombre,
                        'categoria'  => $p->draw->categoria->nombre,
                        'ronda'      => $this->nombreRonda($p->ronda),
                        'rival'      => $p->jugador1_id == $id ? $p->jugador2 : $p->jugador1,
                        'resultado'  => $p->resultado,
                        'gano'       => $p->ganador_id == $id,
                    ]);
                }

                foreach ($masterPartidos as $p) {
                    $anio = $p->grupo->master->torneo->fecha_inicio?->year ?? $p->created_at->year;
                    $todos->push([
                        'anio'       => $anio,
                        'torneo_id'  => $p->grupo->master->torneo->id,
                        'torneo'     => $p->grupo->master->torneo->nombre,
                        'categoria'  => $p->grupo->master->categoria->nombre,
                        'ronda'      => 'Zona – Jornada ' . $p->jornada,
                        'rival'      => $p->jugador1_id == $id ? $p->jugador2 : $p->jugador1,
                        'resultado'  => $p->resultado,
                        'gano'       => $p->ganador_id == $id,
                    ]);
                }

                foreach ($masterFinales as $p) {
                    $anio = $p->master->torneo->fecha_inicio?->year ?? $p->created_at->year;
                    $todos->push([
                        'anio'       => $anio,
                        'torneo_id'  => $p->master->torneo->id,
                        'torneo'     => $p->master->torneo->nombre,
                        'categoria'  => $p->master->categoria->nombre,
                        'ronda'      => $p->label(),
                        'rival'      => $p->jugador1_id == $id ? $p->jugador2 : $p->jugador1,
                        'resultado'  => $p->resultado,
                        'gano'       => $p->ganador_id == $id,
                    ]);
                }

                // Años disponibles para este jugador
                $misAniosJugador = $todos->pluck('anio')->unique()->sortDesc()->values();

                // Torneos disponibles (filtrados por año si corresponde)
                $misTorneosJugador = $todos
                    ->when($this->misAnio !== '', fn($c) => $c->where('anio', (int)$this->misAnio))
                    ->unique('torneo_id')
                    ->sortBy('torneo')
                    ->values()
                    ->map(fn($item) => ['id' => $item['torneo_id'], 'nombre' => $item['torneo']]);

                // Aplicar filtros
                $todosFiltrados = $todos
                    ->when($this->misAnio !== '', fn($c) => $c->where('anio', (int)$this->misAnio))
                    ->when($this->misTorneoId, fn($c) => $c->where('torneo_id', $this->misTorneoId));

                $misPartidosPorAnio = $todosFiltrados->sortByDesc('anio')->groupBy('anio')->all();
            }
        }

        $anos = Torneo::whereNotNull('fecha_inicio')
            ->get()
            ->map(fn($t) => \Carbon\Carbon::parse($t->fecha_inicio)->year)
            ->unique()->sortDesc()->values();

        $misAniosJugador   = $misAniosJugador   ?? collect();
        $misTorneosJugador = $misTorneosJugador ?? collect();

        return view('livewire.bienvenida', compact(
            'torneos', 'clubNombre', 'clubCiudad', 'panelInfo',
            'categoriasData', 'categorias', 'torneosFinalizados',
            'jugadores', 'jugadorSeleccionado', 'misPartidosPorAnio', 'anos',
            'misAniosJugador', 'misTorneosJugador'
        ))->layout('layouts.publica');
    }
}
