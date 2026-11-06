<?php

namespace App\Livewire;

use App\Models\Master;
use App\Models\MasterFinal;
use App\Models\MasterGrupo;
use App\Models\MasterJugadorGrupo;
use App\Models\MasterPartido;
use App\Models\Ranking;
use App\Models\Jugador;
use App\Models\Torneo;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MasterDetalle extends Component
{
    public Torneo $torneo;
    public Master $master;

    // Modal asignar jugador
    public bool   $modalAsignar    = false;
    public ?int   $asignarGrupoId  = null;
    public ?int   $asignarPosicion = null;
    public string $buscarJugador   = '';

    // Modal resultado zona
    public bool   $modalResultado = false;
    public ?int   $partidoId      = null;
    public string $resultado      = '';
    public string $ganadorId      = '';
    public bool   $esWO           = false;
    public bool   $esRetiro       = false;

    // Modal resultado fase final
    public bool   $modalResultadoFinal = false;
    public ?int   $finalId             = null;

    // Sets individuales (compartidos por ambos modales)
    public string $s1j1 = '', $s1j2 = '';
    public string $s2j1 = '', $s2j2 = '';
    public string $s3j1 = '', $s3j2 = '';

    // Confirmación quitar jugador
    public bool $confirmQuitarJugador = false;
    public ?int $quitarGrupoId        = null;
    public ?int $quitarJugadorId      = null;

    // Modal retirar jugador (lesión) + elegir suplente
    public bool $modalRetirar     = false;
    public ?int $retirarGrupoId   = null;
    public ?int $retirarJugadorId = null;

    // Dobles: empate total entre los mejores segundos (se elige a mano)
    public bool  $modalEmpate    = false;
    public array $empateSegundos = [];

    // Dobles con 8 clasificados: empate total en el corte de los mejores terceros (casillas + Confirmar)
    public bool  $modalEmpateTerceros = false;
    public array $empateTerceros      = [];
    public int   $cuposTerceros       = 0;
    public array $tercerosElegidos    = [];

    public function mount(Torneo $torneo, Master $master): void
    {
        $this->torneo = $torneo;
        $this->master = $master;
    }

    /** Formato de dobles: 2 a 4 zonas de 3 o 4 parejas (ver Master::clasificacionDobles()) */
    private function esDobles(): bool
    {
        return (bool) $this->master->categoria->es_dobles;
    }

    /**
     * Dobles: con la fase final generada no se tocan los resultados de zona (cambiarían quién
     * clasifica y los cruces quedarían viejos). Hay que anular la fase final primero.
     */
    private function zonaBloqueadaPorFaseFinal(): bool
    {
        return $this->esDobles() && MasterFinal::where('master_id', $this->master->id)->exists();
    }

    private const MSG_ZONA_BLOQUEADA = 'Hay una fase final generada: anulá la fase final primero.';

    // ── Asignar jugador ────────────────────────────────────────────────

    public function abrirAsignar(int $grupoId, int $posicion): void
    {
        $this->asignarGrupoId  = $grupoId;
        $this->asignarPosicion = $posicion;
        $this->buscarJugador   = '';
        $this->modalAsignar    = true;
    }

    public function asignarJugador(int $jugadorId): void
    {
        $grupo = MasterGrupo::findOrFail($this->asignarGrupoId);

        if ($grupo->tieneRetirado()) {
            session()->flash('error', 'La zona tiene un jugador retirado: no se pueden cambiar jugadores.');
            $this->resetAsignar();
            return;
        }

        // Zona de 3 (dobles): con partidos generados no se regeneran solos, así que no se puede cambiar
        if ($grupo->esDeTres()) {
            session()->flash('error', 'Zona de 3 con partidos generados: para cambiar una pareja, quitala (✕) y volvé a asignarla.');
            $this->resetAsignar();
            return;
        }

        $otrosGrupos = $this->master->grupos()->where('id', '!=', $this->asignarGrupoId)->pluck('id');
        if (MasterJugadorGrupo::whereIn('grupo_id', $otrosGrupos)->where('jugador_id', $jugadorId)->exists()) {
            session()->flash('error', $this->esDobles() ? 'La pareja ya está en otra zona.' : 'El jugador ya está en la otra zona.');
            $this->resetAsignar();
            return;
        }

        $grupo->jugadoresGrupo()->where('posicion', $this->asignarPosicion)->delete();
        $grupo->jugadoresGrupo()->where('jugador_id', $jugadorId)->delete();

        MasterJugadorGrupo::create([
            'grupo_id'   => $this->asignarGrupoId,
            'jugador_id' => $jugadorId,
            'posicion'   => $this->asignarPosicion,
        ]);

        if ($grupo->fresh()->jugadoresGrupo()->count() === 4) {
            $grupo->generarPartidos();
        }

        $this->resetAsignar();
        session()->flash('ok', 'Jugador asignado.');
    }

    public function generarPartidos(int $grupoId): void
    {
        MasterGrupo::findOrFail($grupoId)->generarPartidos();
        session()->flash('ok', 'Partidos generados.');
    }

    public function confirmarQuitarJugador(int $grupoId, int $jugadorId): void
    {
        $this->quitarGrupoId        = $grupoId;
        $this->quitarJugadorId      = $jugadorId;
        $this->confirmQuitarJugador = true;
    }

    public function quitarJugador(): void
    {
        $grupo = MasterGrupo::findOrFail($this->quitarGrupoId);

        if ($grupo->tieneRetirado()) {
            $this->cancelarQuitarJugador();
            session()->flash('error', 'La zona tiene un jugador retirado: no se pueden quitar jugadores.');
            return;
        }

        $grupo->partidos()->delete();
        $grupo->jugadoresGrupo()->where('jugador_id', $this->quitarJugadorId)->delete();

        // Si hay fase final, limpiarla
        $this->limpiarFaseFinal();

        $this->confirmQuitarJugador = false;
        $this->quitarGrupoId        = null;
        $this->quitarJugadorId      = null;
        session()->flash('ok', 'Jugador quitado.');
    }

    public function cancelarQuitarJugador(): void
    {
        $this->confirmQuitarJugador = false;
        $this->quitarGrupoId        = null;
        $this->quitarJugadorId      = null;
    }

    // ── Retiro por lesión + suplente ───────────────────────────────────

    public function abrirRetirar(int $grupoId, int $jugadorId): void
    {
        $grupo = MasterGrupo::findOrFail($grupoId);

        if ($error = $this->validarRetiro($grupo, $jugadorId)) {
            session()->flash('error', $error);
            return;
        }

        $this->retirarGrupoId   = $grupoId;
        $this->retirarJugadorId = $jugadorId;
        $this->buscarJugador    = '';
        $this->modalRetirar     = true;
    }

    public function retirarJugador(int $suplenteId): void
    {
        $grupo = MasterGrupo::findOrFail($this->retirarGrupoId);

        if ($error = $this->validarRetiro($grupo, $this->retirarJugadorId)) {
            session()->flash('error', $error);
            $this->resetRetirar();
            return;
        }

        $inscripto = Jugador::whereKey($suplenteId)
            ->whereHas('inscripciones', fn($q) =>
                $q->where('torneo_id', $this->master->torneo_id)
                  ->where('categoria_id', $this->master->categoria_id)
            )->exists();

        $enAlgunaZona = MasterJugadorGrupo::whereIn('grupo_id', $this->master->grupos()->pluck('id'))
            ->where('jugador_id', $suplenteId)
            ->exists();

        if (!$inscripto || $enAlgunaZona) {
            session()->flash('error', 'El suplente tiene que estar inscripto en la categoría y no estar en ninguna zona.');
            $this->resetRetirar();
            return;
        }

        DB::transaction(function () use ($grupo, $suplenteId) {
            $grupo->jugadoresGrupo()
                ->where('jugador_id', $this->retirarJugadorId)
                ->update(['retirado' => true]);

            MasterJugadorGrupo::create([
                'grupo_id'       => $grupo->id,
                'jugador_id'     => $suplenteId,
                'posicion'       => $grupo->jugadoresGrupo()->max('posicion') + 1,
                'reemplaza_a_id' => $this->retirarJugadorId,
            ]);

            $grupo->aplicarReemplazos();
        });

        $this->recalcularRankingMaster();
        $this->resetRetirar();
        session()->flash('ok', 'Jugador retirado. El suplente ocupa su lugar en los partidos pendientes.');
    }

    /** Devuelve el motivo por el que no se puede retirar, o null si se puede */
    private function validarRetiro(MasterGrupo $grupo, ?int $jugadorId): ?string
    {
        if ((int) $grupo->master_id !== (int) $this->master->id) return 'Zona inválida.';

        if (MasterFinal::where('master_id', $this->master->id)->exists()) {
            return 'La fase final ya está generada: no se puede retirar jugadores de zona.';
        }

        if (!$grupo->jornada1Completa()) {
            return $grupo->esDeTres()
                ? 'Primero hay que terminar la Jornada 1 de la zona (el partido con resultado).'
                : 'Primero hay que terminar la Jornada 1 de la zona (los 2 partidos con resultado).';
        }

        $jg = $grupo->jugadoresGrupo()->where('jugador_id', $jugadorId)->first();
        if (!$jg)          return 'El jugador no pertenece a la zona.';
        if ($jg->retirado) return 'El jugador ya está retirado.';

        $tienePendientes = $grupo->partidos()
            ->whereNull('ganador_id')
            ->where(fn($q) => $q->where('jugador1_id', $jugadorId)->orWhere('jugador2_id', $jugadorId))
            ->exists();

        if (!$tienePendientes) return 'El jugador no tiene partidos pendientes.';

        return null;
    }

    public function cancelarRetirar(): void
    {
        $this->resetRetirar();
    }

    // ── Resultados zona ────────────────────────────────────────────────

    public function abrirResultado(int $partidoId): void
    {
        $partido = MasterPartido::findOrFail($partidoId);

        if ($this->zonaBloqueadaPorFaseFinal()) {
            session()->flash('error', self::MSG_ZONA_BLOQUEADA);
            return;
        }

        if ($partido->jornada === 1 && $partido->grupo->jornada1Bloqueada()) {
            session()->flash('error', $this->mensajeJ1Bloqueada($partido->grupo));
            return;
        }

        $this->partidoId = $partidoId;
        $this->ganadorId = (string) ($partido->ganador_id ?? '');
        $this->esWO      = $partido->resultado === 'W.O.';
        $this->esRetiro  = !$this->esWO && str_ends_with(trim($partido->resultado ?? ''), 'ret.');

        if (!$this->esWO) {
            $raw = preg_replace('/\s*ret\.\s*$/i', '', $partido->resultado ?? '');
            $this->resultado = $raw;
            $this->parsearSets($raw);
            // Si el ganador era jugador2, los sets almacenados están con el ganador primero;
            // invertimos los campos para que cada columna muestre los games reales del jugador.
            if ($partido->ganador_id && (string)$partido->jugador2_id === (string)$partido->ganador_id) {
                [$this->s1j1, $this->s1j2] = [$this->s1j2, $this->s1j1];
                [$this->s2j1, $this->s2j2] = [$this->s2j2, $this->s2j1];
                [$this->s3j1, $this->s3j2] = [$this->s3j2, $this->s3j1];
            }
        } else {
            $this->resultado = '';
        }
        $this->modalResultado = true;
    }

    public function guardarResultado(): void
    {
        $partido    = MasterPartido::findOrFail($this->partidoId);
        $flipSets   = !$this->esWO && (string)$partido->jugador2_id === (string)$this->ganadorId;
        $this->resultado = $this->buildResultado($flipSets);

        $this->validate([
            'resultado' => 'required|string|max:50',
            'ganadorId' => 'required|exists:jugadores,id',
        ], [
            'resultado.required' => 'Ingresá al menos el Set 1.',
            'ganadorId.required' => 'Seleccioná el ganador.',
        ]);

        $esJornada1  = $partido->jornada === 1;

        if ($this->zonaBloqueadaPorFaseFinal()) {
            session()->flash('error', self::MSG_ZONA_BLOQUEADA);
            $this->resetResultado();
            return;
        }

        if ($esJornada1 && $partido->grupo->jornada1Bloqueada()) {
            session()->flash('error', $this->mensajeJ1Bloqueada($partido->grupo));
            $this->resetResultado();
            return;
        }

        if ($esJornada1 && $partido->ganador_id) {
            $partido->grupo->limpiarCruces();
            $this->limpiarFaseFinal();
        }

        $partido->update([
            'resultado'  => trim($this->resultado),
            'ganador_id' => $this->ganadorId,
        ]);

        if ($esJornada1) {
            $partido->grupo->actualizarCruces();
        }

        $this->recalcularRankingMaster();
        $this->resetResultado();
        session()->flash('ok', 'Resultado guardado.');
    }

    public function anularResultado(int $partidoId): void
    {
        $partido = MasterPartido::findOrFail($partidoId);

        if ($this->zonaBloqueadaPorFaseFinal()) {
            session()->flash('error', self::MSG_ZONA_BLOQUEADA);
            return;
        }

        if ($partido->jornada === 1 && $partido->grupo->jornada1Bloqueada()) {
            session()->flash('error', $this->mensajeJ1Bloqueada($partido->grupo));
            return;
        }

        if ($partido->jornada === 1) {
            $partido->grupo->limpiarCruces();
        }

        // Si se anula cualquier resultado de zona, limpiar fase final
        $this->limpiarFaseFinal();

        $partido->update(['resultado' => null, 'ganador_id' => null]);
        $this->recalcularRankingMaster();
        session()->flash('ok', 'Resultado anulado.');
    }

    private function mensajeJ1Bloqueada(MasterGrupo $grupo): string
    {
        // Zona de 3: J2/J3 dependen del resultado de J1 (anular J2/J3 sí está permitido)
        return $grupo->esDeTres()
            ? 'Ya hay resultados en J2/J3, que dependen de J1: primero anulá los resultados de J2 y J3 y después corregí la Jornada 1.'
            : 'La zona tiene un retirado y ya hay resultados en J2/J3: la Jornada 1 no se puede modificar.';
    }

    // ── Fase Final ─────────────────────────────────────────────────────

    public function generarFaseFinal(?int $mejorSegundoElegido = null): void
    {
        if ($this->esDobles()) {
            $this->generarFaseFinalDobles($mejorSegundoElegido);
            return;
        }

        $grupos = $this->master->grupos()->get();
        if ($grupos->count() !== 2) return;

        foreach ($grupos as $g) {
            if (!$g->estaCompleto()) {
                session()->flash('error', 'Completá todos los partidos de las zonas primero.');
                return;
            }
        }

        $zona1 = $grupos->firstWhere('numero', 1);
        $zona2 = $grupos->firstWhere('numero', 2);

        // Un retirado nunca clasifica (calcularPosiciones ya los manda al final; esto es un resguardo)
        $sinRetirados = fn(array $pos) => array_values(array_filter($pos, fn($s) => !$s['retirado']));
        $pos1 = $sinRetirados($zona1->calcularPosiciones());
        $pos2 = $sinRetirados($zona2->calcularPosiciones());

        $p1z1 = $pos1[0]['jugador']->id ?? null; // 1° Zona 1
        $p2z1 = $pos1[1]['jugador']->id ?? null; // 2° Zona 1
        $p1z2 = $pos2[0]['jugador']->id ?? null; // 1° Zona 2
        $p2z2 = $pos2[1]['jugador']->id ?? null; // 2° Zona 2

        if (!$p1z1 || !$p2z1 || !$p1z2 || !$p2z2) {
            session()->flash('error', 'No se pudieron determinar los clasificados.');
            return;
        }

        // Limpiar fase final anterior
        $this->limpiarFaseFinal();

        // Semifinal 1: 1° Zona 1 vs 2° Zona 2
        MasterFinal::create([
            'master_id'   => $this->master->id,
            'tipo'        => 'semifinal_1',
            'jugador1_id' => $p1z1,
            'jugador2_id' => $p2z2,
        ]);

        // Semifinal 2: 1° Zona 2 vs 2° Zona 1
        MasterFinal::create([
            'master_id'   => $this->master->id,
            'tipo'        => 'semifinal_2',
            'jugador1_id' => $p1z2,
            'jugador2_id' => $p2z1,
        ]);

        // Final (vacía, se llena al terminar semis)
        MasterFinal::create([
            'master_id' => $this->master->id,
            'tipo'      => 'final',
        ]);

        $this->master->update(['estado' => 'en_curso']);
        session()->flash('ok', 'Fase final generada. Ingresá los resultados de las semifinales.');
    }

    /** Modal de empate de terceros (8 clasificados): confirma las parejas marcadas con las casillas */
    public function confirmarTerceros(): void
    {
        $elegidos = array_values(array_unique(array_map('intval', $this->tercerosElegidos)));
        if (count($elegidos) !== $this->cuposTerceros || array_diff($elegidos, $this->empateTerceros)) {
            $this->addError('tercerosElegidos', "Marcá exactamente {$this->cuposTerceros} " . ($this->cuposTerceros === 1 ? 'pareja.' : 'parejas.'));
            return;
        }
        $this->generarFaseFinalDobles(null, $elegidos);
    }

    /**
     * Dobles: borra la fase final completa (cuartos, semis y final con sus resultados) y deja el
     * Master en 'pendiente'. Los resultados de zona no se tocan. Es el paso previo para poder
     * corregir un resultado de zona; después se vuelve a generar la fase final.
     */
    public function anularFaseFinal(): void
    {
        if (!$this->esDobles()) return;

        $this->limpiarFaseFinal();
        $this->recalcularRankingMaster();
        session()->flash('ok', 'Fase final anulada. Ya podés corregir los resultados de zona y volver a generarla.');
    }

    /**
     * $mejorSegundoElegido: elección manual cuando hay empate total entre los mejores segundos.
     * $tercerosElegidos: ídem para los mejores terceros (8 clasificados).
     */
    private function generarFaseFinalDobles(?int $mejorSegundoElegido, array $tercerosElegidos = []): void
    {
        if ($this->master->clasificanOcho()) {
            $this->generarCuartos($tercerosElegidos);
            return;
        }

        $grupos = $this->master->grupos()->get();
        if ($grupos->count() < 2 || $grupos->count() > 4) {
            session()->flash('error', 'El formato de dobles necesita de 2 a 4 zonas.');
            return;
        }

        foreach ($grupos as $g) {
            if (!$g->estaCompleto()) {
                session()->flash('error', 'Completá todos los partidos de las zonas primero.');
                return;
            }
        }

        $clasificacion = $this->master->clasificacionDobles($mejorSegundoElegido);

        if ($clasificacion['empatados']) {
            $this->empateSegundos = $clasificacion['empatados'];
            $this->modalEmpate    = true;
            return;
        }

        if (!$clasificacion['semis']) {
            session()->flash('error', 'No se pudieron determinar los clasificados.');
            return;
        }

        $this->limpiarFaseFinal();

        foreach ($clasificacion['semis'] as $i => [$j1, $j2]) {
            MasterFinal::create([
                'master_id'   => $this->master->id,
                'tipo'        => 'semifinal_' . ($i + 1),
                'jugador1_id' => $j1,
                'jugador2_id' => $j2,
            ]);
        }

        MasterFinal::create([
            'master_id' => $this->master->id,
            'tipo'      => 'final',
        ]);

        $this->master->update(['estado' => 'en_curso']);
        $this->resetEmpate();
        session()->flash('ok', 'Fase final generada. Ingresá los resultados de las semifinales.');
    }

    /** Dobles con 8 clasificados: 4 cuartos con las parejas, semis y final vacías */
    private function generarCuartos(array $tercerosElegidos): void
    {
        $grupos = $this->master->grupos()->get();
        if (!in_array($grupos->count(), [3, 4], true)) {
            session()->flash('error', 'Con 8 clasificados hacen falta 3 o 4 zonas.');
            return;
        }

        if ($grupos->contains(fn($g) => !$g->estaCompleto())) {
            session()->flash('error', 'Completá todos los partidos de las zonas primero.');
            return;
        }

        $clasificacion = $this->master->clasificacionDobles(null, $tercerosElegidos);

        if ($clasificacion['empate_terceros']) {
            $this->empateTerceros      = $clasificacion['empate_terceros'];
            $this->cuposTerceros       = $clasificacion['cupos_terceros'];
            $this->tercerosElegidos    = [];
            $this->resetErrorBag('tercerosElegidos');
            $this->modalEmpateTerceros = true;
            return;
        }

        if (!$clasificacion['cuartos']) {
            session()->flash('error', 'No se pudieron determinar los clasificados.');
            return;
        }

        DB::transaction(function () use ($clasificacion) {
            $this->limpiarFaseFinal();

            foreach ($clasificacion['cuartos'] as $i => [$j1, $j2]) {
                MasterFinal::create([
                    'master_id'   => $this->master->id,
                    'tipo'        => 'cuartos_' . ($i + 1),
                    'jugador1_id' => $j1,
                    'jugador2_id' => $j2,
                ]);
            }
            foreach (['semifinal_1', 'semifinal_2', 'final'] as $tipo) {
                MasterFinal::create(['master_id' => $this->master->id, 'tipo' => $tipo]);
            }

            $this->master->update(['estado' => 'en_curso']);
        });

        $this->resetEmpate();
        session()->flash('ok', 'Fase final generada. Ingresá los resultados de los cuartos de final.');
    }

    private function resetEmpate(): void
    {
        $this->modalEmpate    = false;
        $this->empateSegundos = [];

        $this->modalEmpateTerceros = false;
        $this->empateTerceros      = [];
        $this->cuposTerceros       = 0;
        $this->tercerosElegidos    = [];
    }

    public function abrirResultadoFinal(int $finalId): void
    {
        $final = MasterFinal::findOrFail($finalId);
        $this->finalId   = $finalId;
        $this->ganadorId = (string) ($final->ganador_id ?? '');
        $this->esWO      = $final->resultado === 'W.O.';
        $this->esRetiro  = !$this->esWO && str_ends_with(trim($final->resultado ?? ''), 'ret.');

        if (!$this->esWO) {
            $raw = preg_replace('/\s*ret\.\s*$/i', '', $final->resultado ?? '');
            $this->resultado = $raw;
            $this->parsearSets($raw);
            if ($final->ganador_id && (string)$final->jugador2_id === (string)$final->ganador_id) {
                [$this->s1j1, $this->s1j2] = [$this->s1j2, $this->s1j1];
                [$this->s2j1, $this->s2j2] = [$this->s2j2, $this->s2j1];
                [$this->s3j1, $this->s3j2] = [$this->s3j2, $this->s3j1];
            }
        } else {
            $this->resultado = '';
        }
        $this->modalResultadoFinal = true;
    }

    public function guardarResultadoFinal(): void
    {
        $final    = MasterFinal::findOrFail($this->finalId);
        $flipSets = !$this->esWO && (string)$final->jugador2_id === (string)$this->ganadorId;
        $this->resultado = $this->buildResultado($flipSets);

        $this->validate([
            'resultado' => 'required|string|max:50',
            'ganadorId' => 'required|exists:jugadores,id',
        ], [
            'resultado.required' => 'Ingresá al menos el Set 1.',
            'ganadorId.required' => 'Seleccioná el ganador.',
        ]);

        // Si se edita una semi y ya había final con resultado, limpiar final
        if (in_array($final->tipo, ['semifinal_1', 'semifinal_2'])) {
            MasterFinal::where('master_id', $this->master->id)
                ->where('tipo', 'final')
                ->update(['jugador1_id' => null, 'jugador2_id' => null, 'resultado' => null, 'ganador_id' => null]);
        }

        // Cuartos (dobles con 8): se vacían su semi y la final, y se vuelve a armar la semi
        if ($final->esCuartos()) {
            $this->vaciarDesdeCuartos($final);
        }

        $final->update([
            'resultado'  => trim($this->resultado),
            'ganador_id' => $this->ganadorId,
        ]);

        if ($final->esCuartos()) {
            $this->actualizarSemis();
            $this->recalcularRankingMaster();
        }

        // Si era una semi, intentar armar la final y actualizar ranking parcial
        if (in_array($final->tipo, ['semifinal_1', 'semifinal_2'])) {
            $this->actualizarFinal();
            $this->recalcularRankingMaster();
        }

        // Si era la final, guardar ranking y marcar como finalizado
        if ($final->tipo === 'final') {
            $this->recalcularRankingMaster();
            $this->master->update(['estado' => 'finalizado']);
        }

        $this->resetResultadoFinal();
        session()->flash('ok', 'Resultado guardado.');
    }

    public function anularResultadoFinal(int $finalId): void
    {
        $final = MasterFinal::findOrFail($finalId);

        // Si se anula una semi, limpiar la final también
        if (in_array($final->tipo, ['semifinal_1', 'semifinal_2'])) {
            MasterFinal::where('master_id', $this->master->id)
                ->where('tipo', 'final')
                ->update(['jugador1_id' => null, 'jugador2_id' => null, 'resultado' => null, 'ganador_id' => null]);
        }

        if ($final->tipo === 'final') {
            $this->master->update(['estado' => 'en_curso']);
        }

        if ($final->esCuartos()) {
            $this->vaciarDesdeCuartos($final);
        }

        $final->update(['resultado' => null, 'ganador_id' => null]);

        if ($final->esCuartos()) {
            $this->actualizarSemis();
        }

        $this->recalcularRankingMaster();
        session()->flash('ok', 'Resultado anulado.');
    }

    /**
     * Al editar o anular un cuarto: la semi a la que alimenta pierde su resultado y la final se
     * vacía entera (como pasa hoy al editar una semi). Si el Master estaba finalizado vuelve a en curso.
     */
    private function vaciarDesdeCuartos(MasterFinal $cuarto): void
    {
        [$semi] = MasterFinal::CUARTOS_A_SEMI[$cuarto->tipo];

        MasterFinal::where('master_id', $this->master->id)
            ->where('tipo', $semi)
            ->update(['resultado' => null, 'ganador_id' => null]);

        MasterFinal::where('master_id', $this->master->id)
            ->where('tipo', 'final')
            ->update(['jugador1_id' => null, 'jugador2_id' => null, 'resultado' => null, 'ganador_id' => null]);

        if ($this->master->estado === 'finalizado') {
            $this->master->update(['estado' => 'en_curso']);
        }
    }

    /** Dobles con 8: cada lugar de las semis es el ganador de su cuarto (null si no se jugó) */
    private function actualizarSemis(): void
    {
        $cuartos = MasterFinal::where('master_id', $this->master->id)
            ->whereIn('tipo', array_keys(MasterFinal::CUARTOS_A_SEMI))
            ->get()->keyBy('tipo');

        foreach (MasterFinal::CUARTOS_A_SEMI as $tipoCuarto => [$semi, $lugar]) {
            MasterFinal::where('master_id', $this->master->id)
                ->where('tipo', $semi)
                ->update([$lugar => $cuartos[$tipoCuarto]?->ganador_id]);
        }
    }

    private function actualizarFinal(): void
    {
        $semi1 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_1')->first();
        $semi2 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_2')->first();

        if (!$semi1?->ganador_id || !$semi2?->ganador_id) return;

        MasterFinal::where('master_id', $this->master->id)
            ->where('tipo', 'final')
            ->update([
                'jugador1_id' => $semi1->ganador_id,
                'jugador2_id' => $semi2->ganador_id,
                'resultado'   => null,
                'ganador_id'  => null,
            ]);
    }

    private function recalcularRankingMaster(): void
    {
        Ranking::where('torneo_id', $this->master->torneo_id)
            ->where('categoria_id', $this->master->categoria_id)
            ->delete();

        // Categorías sin ranking (ej. Dobles): solo se limpian las filas, no se escriben puntos
        if ($this->master->categoria->sin_ranking) return;

        $puntos = [];

        $semi1 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_1')->first();
        $semi2 = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'semifinal_2')->first();
        $final = MasterFinal::where('master_id', $this->master->id)->where('tipo', 'final')->first();

        // Jugadores en fase final (para no darles 40 pts de zona)
        $enFaseFinal = array_filter(array_unique([
            $semi1?->jugador1_id, $semi1?->jugador2_id,
            $semi2?->jugador1_id, $semi2?->jugador2_id,
        ]));

        // Resguardo: 8 clasificados es solo para categorías sin ranking (no se llega acá), pero si
        // alguna vez se llegara, los de cuartos tampoco son "no clasificados"
        if ($this->master->clasificanOcho()) {
            $enFaseFinal = array_merge($enFaseFinal, MasterFinal::where('master_id', $this->master->id)
                ->whereIn('tipo', array_keys(MasterFinal::CUARTOS_A_SEMI))
                ->get()->flatMap(fn($f) => [$f->jugador1_id, $f->jugador2_id])->filter()->all());
        }

        if ($final?->ganador_id) {
            $puntos[$final->ganador_id]  = 100;
            $puntos[$final->perdedor_id()] = 80;
        }
        if ($semi1?->ganador_id) $puntos[$semi1->perdedor_id()] = 60;
        if ($semi2?->ganador_id) $puntos[$semi2->perdedor_id()] = 60;

        // No clasificados (3° y 4° de zonas completas)
        foreach ($this->master->grupos()->get() as $grupo) {
            if (!$grupo->estaCompleto()) continue;
            foreach ($grupo->calcularPosiciones() as $s) {
                $jId = $s['jugador']->id;
                if (!in_array($jId, $enFaseFinal) && !isset($puntos[$jId])) {
                    $puntos[$jId] = 40;
                }
            }
        }

        foreach ($puntos as $jugadorId => $pts) {
            if (!$jugadorId) continue;
            Ranking::updateOrCreate(
                [
                    'torneo_id'    => $this->master->torneo_id,
                    'jugador_id'   => $jugadorId,
                    'categoria_id' => $this->master->categoria_id,
                ],
                [
                    'ronda_eliminado' => match(true) {
                        $pts >= 100 => 0,
                        $pts >= 80  => 1,
                        $pts >= 60  => 2,
                        default     => 3,
                    },
                    'puntos' => $pts,
                ]
            );
        }
    }

    private function limpiarFaseFinal(): void
    {
        MasterFinal::where('master_id', $this->master->id)->delete();
        Ranking::where('torneo_id', $this->master->torneo_id)
            ->where('categoria_id', $this->master->categoria_id)
            ->delete();
        $this->master->update(['estado' => 'pendiente']);
    }

    // ── Reset helpers ──────────────────────────────────────────────────

    private function resetAsignar(): void
    {
        $this->modalAsignar    = false;
        $this->asignarGrupoId  = null;
        $this->asignarPosicion = null;
        $this->buscarJugador   = '';
    }

    private function resetRetirar(): void
    {
        $this->modalRetirar     = false;
        $this->retirarGrupoId   = null;
        $this->retirarJugadorId = null;
        $this->buscarJugador    = '';
    }

    private function resetResultado(): void
    {
        $this->modalResultado = false;
        $this->partidoId      = null;
        $this->resultado      = '';
        $this->ganadorId      = '';
        $this->esWO           = false;
        $this->esRetiro       = false;
        $this->resetSets();
    }

    private function resetResultadoFinal(): void
    {
        $this->modalResultadoFinal = false;
        $this->finalId             = null;
        $this->resultado           = '';
        $this->ganadorId           = '';
        $this->esWO                = false;
        $this->esRetiro            = false;
        $this->resetSets();
    }

    // ── Helpers sets ──────────────────────────────────────────────────

    private function parsearSets(string $resultado): void
    {
        $this->resetSets();
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
        $result = implode(' ', $sets);
        if ($this->esRetiro) {
            return $result !== '' ? $result . ' ret.' : 'ret.';
        }
        return $result;
    }

    private function resetSets(): void
    {
        $this->s1j1 = $this->s1j2 = '';
        $this->s2j1 = $this->s2j2 = '';
        $this->s3j1 = $this->s3j2 = '';
    }

    public function cerrarModales(): void
    {
        $this->resetAsignar();
        $this->resetRetirar();
        $this->resetResultado();
        $this->resetResultadoFinal();
        $this->resetEmpate();
    }

    // ── Render ─────────────────────────────────────────────────────────

    public function render()
    {
        $grupos = $this->master->grupos()
            ->with([
                'jugadoresGrupo.jugador',
                'jugadoresGrupo.reemplazaA',
                'partidos.jugador1',
                'partidos.jugador2',
                'partidos.ganador',
            ])
            ->get();

        $posiciones = [];
        foreach ($grupos as $grupo) {
            $posiciones[$grupo->id] = $grupo->calcularPosiciones();
        }

        $jugadoresAsignados = collect();
        foreach ($grupos as $grupo) {
            $jugadoresAsignados = $jugadoresAsignados->merge(
                $grupo->jugadoresGrupo->pluck('jugador_id')
            );
        }

        $jugadoresDisponibles = Jugador::whereHas('inscripciones', fn($q) =>
                $q->where('torneo_id', $this->master->torneo_id)
                  ->where('categoria_id', $this->master->categoria_id)
            )
            ->when($this->buscarJugador, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('apellido', 'like', '%' . $this->buscarJugador . '%')
                       ->orWhere('nombre', 'like', '%' . $this->buscarJugador . '%')
                )
            )
            ->orderBy('apellido')->orderBy('nombre')
            ->get();

        $partidoActivo = $this->partidoId
            ? MasterPartido::with(['jugador1', 'jugador2'])->find($this->partidoId)
            : null;

        $finalActiva = $this->finalId
            ? MasterFinal::with(['jugador1', 'jugador2'])->find($this->finalId)
            : null;

        $faseFinal = MasterFinal::with(['jugador1', 'jugador2', 'ganador'])
            ->where('master_id', $this->master->id)
            ->orderByRaw('FIELD(tipo, ' . MasterFinal::ORDEN . ')')
            ->get();

        $dobles        = $this->esDobles();
        $clasificacion = $dobles ? $this->master->clasificacionDobles() : null;

        // En dobles "ambas" son todas las zonas (2 a 4)
        $ambasZonasCompletas = $dobles
            ? $clasificacion['completo']
            : $grupos->count() === 2 && $grupos->every(fn($g) => $g->estaCompleto());

        $empateCandidatos = $this->modalEmpate && $clasificacion
            ? collect($clasificacion['segundos'])->filter(fn($s) => in_array($s['jugador']->id, $this->empateSegundos))->values()
            : collect();

        $empateTercerosCandidatos = $this->modalEmpateTerceros && $clasificacion
            ? collect($clasificacion['terceros'] ?? [])->filter(fn($s) => in_array($s['jugador']->id, $this->empateTerceros))->values()
            : collect();

        return view('livewire.master-detalle', compact(
            'grupos', 'posiciones',
            'jugadoresDisponibles', 'jugadoresAsignados',
            'partidoActivo', 'faseFinal', 'finalActiva',
            'ambasZonasCompletas', 'dobles', 'clasificacion', 'empateCandidatos', 'empateTercerosCandidatos'
        ))->layout('layouts.app');
    }
}
