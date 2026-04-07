<div>
    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('torneo.detalle', $torneo->id) }}"
               class="text-sm text-green-700 hover:underline mb-1 inline-block">← Volver al torneo</a>
            <h1 class="text-2xl font-bold text-gray-800">
                Master — {{ $torneo->nombre }} — Categoría {{ $master->categoria->nombre }}
            </h1>
            <p class="text-sm text-gray-500">8 jugadores · 2 zonas · Round Robin</p>
        </div>
    </div>

    @if(session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('ok') }}
        </div>
    @endif

    <!-- Dos zonas lado a lado -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        @foreach($grupos as $grupo)
        @php
            $jugadoresGrupo = $grupo->jugadoresGrupo->keyBy('posicion');
            $partidos       = $grupo->partidos->groupBy('jornada');
            $pos            = $posiciones[$grupo->id] ?? [];
            $hayPartidos    = $grupo->partidos->isNotEmpty();
        @endphp

        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-lg font-bold text-green-700 mb-4 flex items-center gap-2">
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">{{ $grupo->nombre }}</span>
            </h2>

            <!-- ── Jugadores del grupo ── -->
            <div class="mb-5">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">Jugadores</h3>
                <div class="grid grid-cols-2 gap-2">
                    @for($pos_num = 1; $pos_num <= 4; $pos_num++)
                    @php $jg = $jugadoresGrupo->get($pos_num); @endphp
                    <div class="flex items-center justify-between border rounded-lg px-3 py-2
                                {{ $jg ? 'bg-gray-50 border-gray-200' : 'bg-white border-dashed border-gray-300' }}">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-400 w-4">{{ $pos_num }}</span>
                            @if($jg)
                                <span class="text-sm font-medium text-gray-800">
                                    {{ $jg->jugador->apellido }}, {{ $jg->jugador->nombre }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400 italic">Vacío</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="abrirAsignar({{ $grupo->id }}, {{ $pos_num }})"
                                    class="text-xs text-green-600 hover:text-green-800 font-medium px-2 py-1 rounded hover:bg-green-50 transition">
                                {{ $jg ? 'Cambiar' : 'Asignar' }}
                            </button>
                            @if($jg)
                            <button wire:click="confirmarQuitarJugador({{ $grupo->id }}, {{ $jg->jugador_id }})"
                                    class="text-xs text-red-500 hover:text-red-700 px-1 py-1 rounded hover:bg-red-50 transition">✕</button>
                            @endif
                        </div>
                    </div>
                    @endfor
                </div>

                @if($jugadoresGrupo->count() === 4 && !$hayPartidos)
                <button wire:click="generarPartidos({{ $grupo->id }})"
                        class="mt-3 w-full text-sm bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition font-medium">
                    Generar Partidos
                </button>
                @endif
            </div>

            @if($hayPartidos)
            <!-- ── Partidos por jornada ── -->
            <div class="mb-5">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">Partidos</h3>
                @foreach([1,2,3] as $jornada)
                @php $matchesJornada = $partidos->get($jornada, collect()); @endphp
                <div class="mb-3">
                    <div class="text-xs font-bold text-gray-500 uppercase mb-1">Jornada {{ $jornada }}</div>
                    @foreach($matchesJornada as $partido)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <div class="flex-1 min-w-0">
                            @if($partido->jugador1_id && $partido->jugador2_id)
                                <span class="text-sm {{ $partido->ganador_id == $partido->jugador1_id ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                    {{ $partido->jugador1->apellido }}
                                </span>
                                <span class="text-gray-400 mx-1 text-xs">vs</span>
                                <span class="text-sm {{ $partido->ganador_id == $partido->jugador2_id ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                    {{ $partido->jugador2->apellido }}
                                </span>
                                @if($partido->resultado)
                                    <span class="ml-2 text-xs text-gray-500 font-mono">{{ $partido->resultado }}</span>
                                @endif
                            @else
                                <span class="text-sm text-gray-400 italic">
                                    @if($jornada == 1)
                                        Por definir
                                    @elseif($jornada == 2)
                                        Se define al terminar Jornada 1
                                    @else
                                        Se define al terminar Jornada 1
                                    @endif
                                </span>
                            @endif
                        </div>
                        @if($partido->jugador1_id && $partido->jugador2_id)
                        <div class="flex items-center gap-1 ml-2 shrink-0">
                            <button wire:click="abrirResultado({{ $partido->id }})"
                                    class="text-xs px-2 py-1 rounded font-medium transition
                                        {{ $partido->ganador_id ? 'text-blue-600 hover:bg-blue-50' : 'bg-green-600 text-white hover:bg-green-700' }}">
                                {{ $partido->ganador_id ? 'Editar' : 'Resultado' }}
                            </button>
                            @if($partido->ganador_id)
                            <button wire:click="anularResultado({{ $partido->id }})"
                                    wire:confirm="¿Anular este resultado?"
                                    class="text-xs text-red-500 hover:text-red-700 px-1 py-1 rounded hover:bg-red-50 transition">✕</button>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            <!-- ── Tabla de posiciones ── -->
            <div>
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">Posiciones</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-green-700 text-white">
                                <th class="px-2 py-1.5 text-left font-medium">#</th>
                                <th class="px-2 py-1.5 text-left font-medium">Jugador</th>
                                <th class="px-1 py-1.5 text-center font-medium">PJ</th>
                                <th class="px-1 py-1.5 text-center font-medium">PG</th>
                                <th class="px-1 py-1.5 text-center font-medium">PP</th>
                                <th class="px-1 py-1.5 text-center font-medium">DIF</th>
                                <th class="px-1 py-1.5 text-center font-medium">SG</th>
                                <th class="px-1 py-1.5 text-center font-medium">SP</th>
                                <th class="px-1 py-1.5 text-center font-medium">DS</th>
                                <th class="px-1 py-1.5 text-center font-medium">GG</th>
                                <th class="px-1 py-1.5 text-center font-medium">GP</th>
                                <th class="px-1 py-1.5 text-center font-medium">DG</th>
                                <th class="px-2 py-1.5 text-center font-bold">PTS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($pos as $i => $s)
                            <tr class="{{ $i === 0 ? 'bg-yellow-50' : ($i === 1 ? 'bg-gray-50' : '') }} hover:bg-gray-50">
                                <td class="px-2 py-1.5 font-bold text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-2 py-1.5 font-semibold text-gray-800 whitespace-nowrap">
                                    {{ $s['jugador']->apellido }}
                                </td>
                                <td class="px-1 py-1.5 text-center text-gray-600">{{ $s['pj'] }}</td>
                                <td class="px-1 py-1.5 text-center text-gray-600">{{ $s['pg'] }}</td>
                                <td class="px-1 py-1.5 text-center text-gray-600">{{ $s['pp'] }}</td>
                                <td class="px-1 py-1.5 text-center {{ $s['dif_part'] > 0 ? 'text-green-600' : ($s['dif_part'] < 0 ? 'text-red-500' : 'text-gray-500') }} font-medium">
                                    {{ $s['dif_part'] > 0 ? '+' : '' }}{{ $s['dif_part'] }}
                                </td>
                                <td class="px-1 py-1.5 text-center text-gray-600">{{ $s['sg'] }}</td>
                                <td class="px-1 py-1.5 text-center text-gray-600">{{ $s['sp'] }}</td>
                                <td class="px-1 py-1.5 text-center {{ $s['dif_set'] > 0 ? 'text-green-600' : ($s['dif_set'] < 0 ? 'text-red-500' : 'text-gray-500') }} font-medium">
                                    {{ $s['dif_set'] > 0 ? '+' : '' }}{{ $s['dif_set'] }}
                                </td>
                                <td class="px-1 py-1.5 text-center text-gray-600">{{ $s['gg'] }}</td>
                                <td class="px-1 py-1.5 text-center text-gray-600">{{ $s['gp'] }}</td>
                                <td class="px-1 py-1.5 text-center {{ $s['dif_game'] > 0 ? 'text-green-600' : ($s['dif_game'] < 0 ? 'text-red-500' : 'text-gray-500') }} font-medium">
                                    {{ $s['dif_game'] > 0 ? '+' : '' }}{{ $s['dif_game'] }}
                                </td>
                                <td class="px-2 py-1.5 text-center font-bold text-green-700 text-sm">{{ $s['puntos'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="px-3 py-3 text-center text-gray-400">
                                    Sin resultados todavía
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
        @endforeach
    </div>

    <!-- ══ Fase Final ═══════════════════════════════════════════════════ -->
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    🏆 Fase Final
                    @if($master->estado === 'finalizado')
                        <span class="text-xs font-medium bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Finalizado</span>
                    @endif
                </h2>
                @if($ambasZonasCompletas && $faseFinal->isEmpty())
                    <button wire:click="generarFaseFinal"
                            class="bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition text-sm font-medium">
                        Generar Fase Final
                    </button>
                @elseif(!$ambasZonasCompletas && $faseFinal->isEmpty())
                    <span class="text-sm text-gray-400 italic">Completá todas las zonas primero</span>
                @endif
            </div>

            @if($faseFinal->isEmpty())
                <p class="text-gray-400 text-sm text-center py-4">
                    Una vez que ambas zonas estén completas, generá la fase final.
                </p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($faseFinal as $match)
                    <div class="border rounded-xl p-4 {{ $match->tipo === 'final' ? 'border-yellow-400 bg-yellow-50' : 'border-gray-200' }}">
                        <div class="text-xs font-bold uppercase tracking-wide mb-3
                                    {{ $match->tipo === 'final' ? 'text-yellow-600' : 'text-gray-500' }}">
                            {{ $match->label() }}
                        </div>

                        @if($match->jugador1_id && $match->jugador2_id)
                            <div class="space-y-2 mb-3">
                                <div class="flex items-center gap-2">
                                    @if($match->ganador_id === $match->jugador1_id)
                                        <span class="text-yellow-500 text-xs">🥇</span>
                                    @else
                                        <span class="w-4"></span>
                                    @endif
                                    <span class="text-sm {{ $match->ganador_id === $match->jugador1_id ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $match->jugador1->apellido }}, {{ $match->jugador1->nombre }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 text-center">vs</div>
                                <div class="flex items-center gap-2">
                                    @if($match->ganador_id === $match->jugador2_id)
                                        <span class="text-yellow-500 text-xs">🥇</span>
                                    @else
                                        <span class="w-4"></span>
                                    @endif
                                    <span class="text-sm {{ $match->ganador_id === $match->jugador2_id ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $match->jugador2->apellido }}, {{ $match->jugador2->nombre }}
                                    </span>
                                </div>
                            </div>

                            @if($match->resultado)
                                <div class="text-center text-xs font-mono text-gray-500 mb-3">{{ $match->resultado }}</div>
                            @endif

                            <div class="flex gap-2 justify-center">
                                <button wire:click="abrirResultadoFinal({{ $match->id }})"
                                        class="text-xs px-3 py-1.5 rounded-lg font-medium transition
                                            {{ $match->ganador_id ? 'text-blue-600 border border-blue-200 hover:bg-blue-50' : 'bg-green-600 text-white hover:bg-green-700' }}">
                                    {{ $match->ganador_id ? 'Editar' : 'Resultado' }}
                                </button>
                                @if($match->ganador_id)
                                <button wire:click="anularResultadoFinal({{ $match->id }})"
                                        wire:confirm="¿Anular este resultado?"
                                        class="text-xs text-red-500 hover:text-red-700 px-2 py-1.5 rounded hover:bg-red-50 transition">✕</button>
                                @endif
                            </div>
                        @else
                            <div class="text-center text-gray-400 text-sm py-2 italic">
                                Se define al terminar las semifinales
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                @if($master->estado === 'finalizado')
                <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 text-center">
                    Puntos otorgados: Campeón 100 · Finalista 80 · Semifinalistas 60 · No clasificados 40
                </div>
                @endif
            @endif
        </div>
    </div>

    <!-- ══ Modal Asignar Jugador ══════════════════════════════════════════ -->
    @if($modalAsignar)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
         @keydown.escape.window="$wire.cerrarModales()">
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4 flex flex-col"
             style="max-height: 90vh;">
            <h3 class="text-lg font-bold text-gray-800 mb-1 shrink-0">Asignar jugador</h3>
            <p class="text-sm text-gray-500 mb-3 shrink-0">Posición {{ $asignarPosicion }} — hacé clic para asignar</p>

            <div class="mb-3 shrink-0">
                <input type="text" wire:model.live.debounce.200ms="buscarJugador"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Buscar jugador..."
                       autocomplete="off">
            </div>

            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 overflow-y-auto flex-1">
                @forelse($jugadoresDisponibles as $j)
                @php $yaAsignado = $jugadoresAsignados->contains($j->id); @endphp
                @if($yaAsignado)
                    <div class="flex items-center gap-3 px-3 py-2.5 opacity-40 bg-gray-50 cursor-not-allowed">
                        <span class="text-sm text-gray-700">
                            <span class="font-medium">{{ $j->apellido }}</span>, {{ $j->nombre }}
                            <span class="text-xs text-gray-400 ml-1">(ya asignado)</span>
                        </span>
                    </div>
                @else
                    <button wire:click="asignarJugador({{ $j->id }})"
                            class="w-full text-left flex items-center gap-3 px-3 py-2.5 hover:bg-green-50 active:bg-green-100 transition cursor-pointer">
                        <span class="w-2 h-2 rounded-full bg-green-400 shrink-0"></span>
                        <span class="text-sm text-gray-800">
                            <span class="font-medium">{{ $j->apellido }}</span>, {{ $j->nombre }}
                        </span>
                    </button>
                @endif
                @empty
                    <div class="px-3 py-4 text-center text-gray-400 text-sm">No hay jugadores inscriptos en esta categoría</div>
                @endforelse
            </div>

            <div class="flex justify-end mt-4 shrink-0">
                <button wire:click="cerrarModales"
                        class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-sm">Cancelar</button>
            </div>
        </div>
    </div>
    @endif

    <!-- ══ Modal Resultado ════════════════════════════════════════════════ -->
    @if($modalResultado && $partidoActivo)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
         @keydown.escape.window="$wire.cerrarModales()">
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
            <h3 class="text-lg font-bold text-gray-800 mb-1">Cargar Resultado</h3>
            <p class="text-sm text-gray-500 mb-4">
                {{ $partidoActivo->jugador1->apellido }} vs {{ $partidoActivo->jugador2->apellido }}
            </p>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultado *</label>
                    <input type="text" wire:model="resultado"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 font-mono"
                           placeholder="Ej: 6-4 6-3 o 6-4 3-6 7-5">
                    @error('resultado') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ganador *</label>
                    <select wire:model="ganadorId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Seleccionar...</option>
                        <option value="{{ $partidoActivo->jugador1_id }}">
                            {{ $partidoActivo->jugador1->apellido }}, {{ $partidoActivo->jugador1->nombre }}
                        </option>
                        <option value="{{ $partidoActivo->jugador2_id }}">
                            {{ $partidoActivo->jugador2->apellido }}, {{ $partidoActivo->jugador2->nombre }}
                        </option>
                    </select>
                    @error('ganadorId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-5">
                <button wire:click="cerrarModales"
                        class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-sm">Cancelar</button>
                <button wire:click="guardarResultado"
                        class="px-4 py-2 rounded-lg bg-green-700 text-white hover:bg-green-800 text-sm font-medium">Guardar</button>
            </div>
        </div>
    </div>
    @endif

    <!-- ══ Modal Resultado Fase Final ═══════════════════════════════════ -->
    @if($modalResultadoFinal && $finalActiva)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
         @keydown.escape.window="$wire.cerrarModales()">
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
            <h3 class="text-lg font-bold text-gray-800 mb-1">{{ $finalActiva->label() }}</h3>
            <p class="text-sm text-gray-500 mb-4">
                {{ $finalActiva->jugador1->apellido }} vs {{ $finalActiva->jugador2->apellido }}
            </p>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultado *</label>
                    <input type="text" wire:model="resultado"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 font-mono"
                           placeholder="Ej: 6-4 6-3 o 6-4 3-6 7-5">
                    @error('resultado') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ganador *</label>
                    <select wire:model="ganadorId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Seleccionar...</option>
                        <option value="{{ $finalActiva->jugador1_id }}">
                            {{ $finalActiva->jugador1->apellido }}, {{ $finalActiva->jugador1->nombre }}
                        </option>
                        <option value="{{ $finalActiva->jugador2_id }}">
                            {{ $finalActiva->jugador2->apellido }}, {{ $finalActiva->jugador2->nombre }}
                        </option>
                    </select>
                    @error('ganadorId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex gap-3 justify-end mt-5">
                <button wire:click="cerrarModales"
                        class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-sm">Cancelar</button>
                <button wire:click="guardarResultadoFinal"
                        class="px-4 py-2 rounded-lg bg-green-700 text-white hover:bg-green-800 text-sm font-medium">Guardar</button>
            </div>
        </div>
    </div>
    @endif

    <!-- ══ Modal Confirmar Quitar Jugador ══════════════════════════════ -->
    @if($confirmQuitarJugador)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
            <h3 class="text-lg font-bold text-gray-800 mb-2">¿Quitar jugador?</h3>
            <p class="text-gray-600 mb-6 text-sm">Se borrarán todos los partidos de esta zona y habrá que reasignar los 4 jugadores.</p>
            <div class="flex gap-3 justify-end">
                <button wire:click="cancelarQuitarJugador"
                        class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-sm">Cancelar</button>
                <button wire:click="quitarJugador"
                        class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm font-medium">Quitar</button>
            </div>
        </div>
    </div>
    @endif
</div>
