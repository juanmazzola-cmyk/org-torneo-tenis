<?php

namespace App\Livewire;

use App\Models\Torneo;
use App\Models\Jugador;
use App\Models\Categoria;
use App\Models\Inscripcion;
use App\Models\Master;
use App\Models\MasterFinal;
use App\Exports\InscripcionesExport;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class TorneoDetalle extends Component
{
    public Torneo $torneo;
    public string $tab = 'inscripciones'; // inscripciones | draws | masters

    // Formulario inscripción individual
    public string $jugador_id = '';
    public string $categoria_id = '';
    public string $observacion = '';
    public ?int $editandoInscripcionId = null;

    public bool $confirmandoEliminarInscripcion = false;
    public ?int $eliminarInscripcionId = null;

    public string $buscarJugador = '';

    // Inscripción masiva
    public string $modoInscripcion = 'individual'; // individual | masiva
    public array  $seleccionados = [];
    public string $categoriaMasiva = '';
    public string $buscarMasiva = '';

    // Para el draw
    public string $drawCategoriaId = '';
    public string $drawTamano = '8';

    // Para el master
    public string $masterCategoriaId = '';
    public string $masterZonas = '2'; // solo categorías de dobles (2 a 4)
    public string $masterClasifican = '4'; // dobles con 3 o 4 zonas: 4 (semis) u 8 (cuartos)

    protected function rules(): array
    {
        return [
            'jugador_id'   => 'required|exists:jugadores,id',
            'categoria_id' => 'required|exists:categorias,id',
            'observacion'  => 'nullable|max:500',
        ];
    }

    public function mount(Torneo $torneo): void
    {
        $this->torneo = $torneo;
        if ($torneo->esMaster()) {
            $this->tab = 'masters';
        }
    }

    public function guardarInscripcion(): void
    {
        $this->validate();

        // Verificar que no tenga más de 2 categorías en este torneo
        if (!$this->editandoInscripcionId) {
            $catCount = Inscripcion::where('torneo_id', $this->torneo->id)
                ->where('jugador_id', $this->jugador_id)
                ->count();
            if ($catCount >= 2) {
                $this->addError('jugador_id', 'El jugador ya está inscripto en 2 categorías en este torneo.');
                return;
            }

            $existe = Inscripcion::where([
                'torneo_id'   => $this->torneo->id,
                'jugador_id'  => $this->jugador_id,
                'categoria_id'=> $this->categoria_id,
            ])->exists();
            if ($existe) {
                $this->addError('categoria_id', 'El jugador ya está inscripto en esa categoría.');
                return;
            }

            if ($this->tipoNoCoincide((int) $this->jugador_id, (int) $this->categoria_id)) {
                $this->addError('jugador_id', 'Las parejas solo se inscriben en categorías de dobles, y los jugadores individuales en las demás.');
                return;
            }
        }

        $data = [
            'torneo_id'   => $this->torneo->id,
            'jugador_id'  => $this->jugador_id,
            'categoria_id'=> $this->categoria_id,
            'observacion' => $this->observacion ?: null,
        ];

        if ($this->editandoInscripcionId) {
            Inscripcion::find($this->editandoInscripcionId)->update([
                'observacion' => $this->observacion ?: null,
            ]);
            session()->flash('ok', 'Inscripción actualizada.');
        } else {
            Inscripcion::create($data);
            session()->flash('ok', 'Jugador inscripto correctamente.');
        }
        $this->resetInscripcion();
    }

    public function editarInscripcion(int $id): void
    {
        $insc = Inscripcion::findOrFail($id);
        $this->editandoInscripcionId = $id;
        $this->jugador_id   = (string) $insc->jugador_id;
        $this->categoria_id = (string) $insc->categoria_id;
        $this->observacion  = $insc->observacion ?? '';
    }

    public function confirmarEliminarInscripcion(int $id): void
    {
        $this->eliminarInscripcionId = $id;
        $this->confirmandoEliminarInscripcion = true;
    }

    public function eliminarInscripcion(): void
    {
        Inscripcion::findOrFail($this->eliminarInscripcionId)->delete();
        session()->flash('ok', 'Inscripción eliminada.');
        $this->reset(['confirmandoEliminarInscripcion', 'eliminarInscripcionId']);
    }

    public function cancelarInscripcion(): void
    {
        $this->reset(['confirmandoEliminarInscripcion', 'eliminarInscripcionId']);
        $this->resetInscripcion();
    }

    public function exportarInscripciones()
    {
        return Excel::download(
            new InscripcionesExport($this->torneo->id, $this->torneo->nombre),
            'inscripciones-' . str()->slug($this->torneo->nombre) . '.xlsx'
        );
    }

    public function crearDraw(): void
    {
        $this->validate([
            'drawCategoriaId' => 'required|exists:categorias,id',
            'drawTamano'      => 'required|in:8,16,32,64',
        ]);

        $draw = $this->torneo->draws()->updateOrCreate(
            ['categoria_id' => $this->drawCategoriaId],
            ['tamano' => $this->drawTamano]
        );

        $draw->generarBracket();

        session()->flash('ok', 'Draw creado. Ahora asigná los jugadores desde el bracket.');
        $this->reset(['drawCategoriaId', 'drawTamano']);
    }

    public function crearMaster(): void
    {
        $this->validate([
            'masterCategoriaId' => 'required|exists:categorias,id',
        ]);

        $categoria = Categoria::find($this->masterCategoriaId);
        $dobles    = (bool) $categoria?->es_dobles;
        if ($dobles) {
            $this->validate(['masterZonas' => 'required|in:2,3,4']);
        }

        // 8 clasificados (cuartos): solo dobles sin ranking con 3 o 4 zonas
        $clasifican = 4;
        if ($dobles && in_array($this->masterZonas, ['3', '4'], true)) {
            $this->validate(['masterClasifican' => 'required|in:4,8']);
            $clasifican = (int) $this->masterClasifican;
            if ($clasifican === 8 && !$categoria->sin_ranking) {
                $this->addError('masterClasifican', 'Con 8 clasificados la categoría tiene que estar marcada "sin ranking".');
                return;
            }
        }

        $master = Master::updateOrCreate(
            ['torneo_id' => $this->torneo->id, 'categoria_id' => $this->masterCategoriaId],
            ['estado' => 'pendiente', 'clasifican' => $clasifican]
        );

        // Al reiniciar un Master existente, la fase final vieja no se borraba con las zonas
        MasterFinal::where('master_id', $master->id)->delete();

        if ($dobles) {
            $master->crearGrupos((int) $this->masterZonas);
            $formato = $clasifican === 8 ? ' (clasifican 8, con cuartos de final)' : '';
            session()->flash('ok', "Torneo de dobles creado con {$this->masterZonas} zonas{$formato}. Asigná las parejas a cada zona (3 o 4 por zona).");
        } else {
            $master->crearGrupos();
            session()->flash('ok', 'Master creado. Asigná los jugadores a cada zona.');
        }

        $this->reset(['masterCategoriaId', 'masterZonas', 'masterClasifican']);
    }

    public function inscribirMasivo(): void
    {
        $this->validate([
            'seleccionados'  => 'required|array|min:1',
            'categoriaMasiva'=> 'required|exists:categorias,id',
        ], [
            'seleccionados.required' => 'Seleccioná al menos un jugador.',
            'seleccionados.min'      => 'Seleccioná al menos un jugador.',
            'categoriaMasiva.required' => 'Seleccioná una categoría.',
        ]);

        $importados = 0;
        $omitidos   = 0;

        foreach ($this->seleccionados as $jugadorId) {
            $catCount = Inscripcion::where('torneo_id', $this->torneo->id)
                ->where('jugador_id', $jugadorId)
                ->count();

            $existe = Inscripcion::where([
                'torneo_id'    => $this->torneo->id,
                'jugador_id'   => $jugadorId,
                'categoria_id' => $this->categoriaMasiva,
            ])->exists();

            if ($catCount >= 2 || $existe || $this->tipoNoCoincide((int) $jugadorId, (int) $this->categoriaMasiva)) {
                $omitidos++;
                continue;
            }

            Inscripcion::create([
                'torneo_id'    => $this->torneo->id,
                'jugador_id'   => $jugadorId,
                'categoria_id' => $this->categoriaMasiva,
            ]);
            $importados++;
        }

        $msg = "{$importados} jugador/es inscriptos";
        if ($omitidos) $msg .= ", {$omitidos} omitido/s (ya inscriptos, límite de 2 categorías o pareja/individual en categoría que no corresponde)";
        session()->flash('ok', $msg . '.');

        $this->reset(['seleccionados', 'categoriaMasiva', 'buscarMasiva']);
    }

    public function toggleTodos(bool $todos, array $ids): void
    {
        $this->seleccionados = $todos ? $ids : [];
    }

    public function updatedCategoriaMasiva(): void
    {
        $this->seleccionados = [];
    }

    public function updatedCategoriaId(): void
    {
        // Si el jugador elegido ya no corresponde a la categoría (pareja vs individual), se descarta
        if (!$this->editandoInscripcionId && $this->jugador_id
            && $this->tipoNoCoincide((int) $this->jugador_id, (int) $this->categoria_id)) {
            $this->jugador_id = '';
        }
    }

    /** Parejas (jugador de categoría es_dobles) solo en categorías de dobles; individuales y Bye en las demás */
    private function filtrarPorTipoCategoria($query, string $categoriaId)
    {
        $dobles = Categoria::where('es_dobles', true)->pluck('id');

        return $dobles->contains((int) $categoriaId)
            ? $query->whereIn('categoria_id', $dobles)
            : $query->where(fn($q) => $q->whereNull('categoria_id')->orWhereNotIn('categoria_id', $dobles));
    }

    private function tipoNoCoincide(int $jugadorId, int $categoriaId): bool
    {
        $jugadorEsPareja = (bool) Jugador::find($jugadorId)?->categoria?->es_dobles;
        $categoriaDobles = (bool) Categoria::find($categoriaId)?->es_dobles;

        return $jugadorEsPareja !== $categoriaDobles;
    }

    private function resetInscripcion(): void
    {
        $this->reset(['jugador_id', 'categoria_id', 'observacion', 'editandoInscripcionId', 'buscarJugador']);
    }

    public function render()
    {
        $inscripciones = Inscripcion::with(['jugador', 'categoria'])
            ->where('torneo_id', $this->torneo->id)
            ->when($this->buscarJugador, fn($q) =>
                $q->whereHas('jugador', fn($q2) =>
                    $q2->where('apellido', 'like', '%' . $this->buscarJugador . '%')
                       ->orWhere('nombre', 'like', '%' . $this->buscarJugador . '%')
                )
            )
            ->join('jugadores', 'inscripciones.jugador_id', '=', 'jugadores.id')
            ->orderBy('jugadores.apellido')
            ->orderBy('jugadores.nombre')
            ->select('inscripciones.*')
            ->get();

        $jugadoresDisponibles = Jugador::orderBy('apellido')->orderBy('nombre')
            ->where(fn($q) =>
                $this->filtrarPorTipoCategoria($q, $this->categoria_id)
                    // Al editar, el select está deshabilitado pero tiene que mostrar al jugador inscripto
                    ->when($this->editandoInscripcionId, fn($q2) => $q2->orWhere('id', $this->jugador_id))
            )
            ->get();

        $jugadoresMasiva = Jugador::orderBy('apellido')->orderBy('nombre')
            ->where(fn($q) => $q->where('apellido', '!=', 'Bye')->orWhere('nombre', '!=', 'Bye'))
            ->where(fn($q) => $this->filtrarPorTipoCategoria($q, $this->categoriaMasiva))
            ->when($this->buscarMasiva, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('apellido', 'like', '%' . $this->buscarMasiva . '%')
                       ->orWhere('nombre',   'like', '%' . $this->buscarMasiva . '%')
                )
            )
            ->get();

        $categorias = Categoria::orderBy('nombre')->get();
        $draws = $this->torneo->draws()->with(['categoria'])->get();
        $masters = Master::with('categoria')->withCount('grupos')->where('torneo_id', $this->torneo->id)->get();
        $masterEsDobles = (bool) $categorias->firstWhere('id', (int) $this->masterCategoriaId)?->es_dobles;

        // IDs de jugadores ya inscriptos en la categoría masiva seleccionada
        $yaInscriptosEnCategoria = $this->categoriaMasiva
            ? Inscripcion::where('torneo_id', $this->torneo->id)
                ->where('categoria_id', $this->categoriaMasiva)
                ->pluck('jugador_id')
                ->toArray()
            : [];

        return view('livewire.torneo-detalle', compact(
            'inscripciones', 'jugadoresDisponibles', 'jugadoresMasiva',
            'categorias', 'draws', 'masters', 'yaInscriptosEnCategoria', 'masterEsDobles'
        ))->layout('layouts.app');
    }
}
