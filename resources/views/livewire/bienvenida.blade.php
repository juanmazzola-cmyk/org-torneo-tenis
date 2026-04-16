<div wire:poll.30s class="flex flex-col"
     style="min-height:100dvh; background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('{{ asset('canchatenis.png') }}') center/cover fixed;">

    {{-- ═══════════════════════════════════════════════════════
         PANEL: RANKING
    ═══════════════════════════════════════════════════════ --}}
    @if($panel === 'ranking')

    <div class="flex flex-col min-h-screen">
        <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
            <div class="max-w-5xl mx-auto px-4 py-3 space-y-2">
                {{-- Fila 1: título --}}
                <div class="flex items-center gap-3">
                    <button wire:click="cerrar" class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</button>
                    <h1 class="text-white font-bold text-lg">🏆 Ranking General</h1>
                </div>
                {{-- Fila 2: solapas de año --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                    @foreach($anos as $anio)
                    <button wire:click="$set('filtroAnio', '{{ $anio }}')"
                            class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold transition
                                   {{ (string)$filtroAnio === (string)$anio
                                       ? 'bg-white text-green-900'
                                       : 'bg-white/15 text-white hover:bg-white/25' }}">
                        {{ $anio }}
                    </button>
                    @endforeach
                    <button wire:click="$set('filtroAnio', '')"
                            class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold transition
                                   {{ $filtroAnio === ''
                                       ? 'bg-white text-green-900'
                                       : 'bg-white/15 text-white hover:bg-white/25' }}">
                        Todos
                    </button>
                </div>
                {{-- Fila 3: solapas de categoría --}}
                @if($categorias->isNotEmpty())
                <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                    <button wire:click="$set('filtroCategoria', '')"
                            class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition
                                   {{ $filtroCategoria === ''
                                       ? 'bg-yellow-300 text-green-900'
                                       : 'bg-white/10 text-green-200 hover:bg-white/20' }}">
                        Todas
                    </button>
                    @foreach($categorias as $cat)
                    <button wire:click="$set('filtroCategoria', '{{ $cat->id }}')"
                            class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition
                                   {{ (string)$filtroCategoria === (string)$cat->id
                                       ? 'bg-yellow-300 text-green-900'
                                       : 'bg-white/10 text-green-200 hover:bg-white/20' }}">
                        Cat. {{ $cat->nombre }}
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <div class="flex-1 max-w-5xl mx-auto w-full px-4 py-8 space-y-10">
            @if(empty($categoriasData))
                <div class="bg-white/10 rounded-2xl p-10 text-center text-green-200">
                    No hay datos de ranking disponibles aún.
                </div>
            @else
                @foreach($categoriasData as $bloque)
                <div>
                    <h2 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                        <span class="bg-white/20 rounded-full px-4 py-1.5 text-sm">
                            Categoría {{ $bloque['categoria']->nombre }}
                        </span>
                        @if(count($bloque['torneos']) > 1)
                        <span class="scroll-hint inline-flex items-center gap-1 bg-white/20 border border-white/40 text-white text-xs font-bold px-2.5 py-1 rounded-full animate-pulse select-none">
                            deslizá &nbsp;›
                        </span>
                        @endif
                    </h2>
                    <div class="scroll-table bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-x-auto shadow-xl">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-white/10 text-green-200 uppercase text-xs tracking-wider">
                                    <th class="px-4 py-3 text-left w-8">#</th>
                                    <th class="px-4 py-3 text-left">Jugador</th>
                                    @foreach($bloque['torneos'] as $torneo)
                                        <th class="px-4 py-3 text-center whitespace-nowrap">
                                            {{ $torneo->nombre }}<br>
                                            <span class="text-green-400 text-xs font-normal normal-case tracking-normal">
                                                Cat. {{ $bloque['categoria']->nombre }}
                                            </span>
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-right text-yellow-300">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach($bloque['jugadores'] as $pos => $fila)
                                <tr class="hover:bg-white/5 transition
                                    {{ $pos === 0 ? 'bg-yellow-400/10' : ($pos === 1 ? 'bg-gray-300/10' : ($pos === 2 ? 'bg-orange-400/10' : '')) }}">
                                    <td class="px-4 py-3 text-center font-bold
                                        {{ $pos === 0 ? 'text-yellow-300' : ($pos === 1 ? 'text-gray-300' : ($pos === 2 ? 'text-orange-300' : 'text-green-300')) }}">
                                        {{ $bloque['hay_torneo_finalizado'] && $pos < 3 ? ($pos === 0 ? '🥇' : ($pos === 1 ? '🥈' : '🥉')) : $pos + 1 }}
                                    </td>
                                    <td class="px-4 py-3 text-white font-semibold">
                                        {{ $fila['jugador']->apellido }}, {{ $fila['jugador']->nombre }}
                                        @if($bloque['hay_torneo_finalizado'] && $pos < 8)
                                            <span class="ml-1 text-xs text-yellow-300">★</span>
                                        @endif
                                    </td>
                                    @foreach($bloque['torneos'] as $torneo)
                                        <td class="px-4 py-3 text-center text-green-200">
                                            {{ $fila['puntos'][$torneo->id] > 0 ? $fila['puntos'][$torneo->id] : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right font-bold text-white text-base">
                                        {{ $fila['total'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
        <div class="text-center pb-5 text-green-700 text-xs">
            Ranking generado automáticamente desde los resultados del draw
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         PANEL: TORNEOS FINALIZADOS
    ═══════════════════════════════════════════════════════ --}}
    @elseif($panel === 'torneos')

    <div class="flex flex-col min-h-screen">
        <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
            <div class="max-w-2xl mx-auto px-4 py-3 space-y-2">
                {{-- Fila 1: título --}}
                <div class="flex items-center gap-3">
                    <button wire:click="cerrar" class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</button>
                    <h1 class="text-white font-bold text-lg">🏅 Torneos finalizados</h1>
                </div>
                {{-- Fila 2: solapas de año --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                    @foreach($anos as $anio)
                    <button wire:click="$set('filtroAnio', '{{ $anio }}')"
                            class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold transition
                                   {{ (string)$filtroAnio === (string)$anio
                                       ? 'bg-white text-green-900'
                                       : 'bg-white/15 text-white hover:bg-white/25' }}">
                        {{ $anio }}
                    </button>
                    @endforeach
                    <button wire:click="$set('filtroAnio', '')"
                            class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold transition
                                   {{ $filtroAnio === ''
                                       ? 'bg-white text-green-900'
                                       : 'bg-white/15 text-white hover:bg-white/25' }}">
                        Todos
                    </button>
                </div>
                {{-- Fila 3: solapas de categoría --}}
                @if($categorias->isNotEmpty())
                <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                    <button wire:click="$set('filtroCategoriaTorneos', '')"
                            class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition
                                   {{ $filtroCategoriaTorneos === ''
                                       ? 'bg-yellow-300 text-green-900'
                                       : 'bg-white/10 text-green-200 hover:bg-white/20' }}">
                        Todas
                    </button>
                    @foreach($categorias as $cat)
                    <button wire:click="$set('filtroCategoriaTorneos', '{{ $cat->id }}')"
                            class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition
                                   {{ (string)$filtroCategoriaTorneos === (string)$cat->id
                                       ? 'bg-yellow-300 text-green-900'
                                       : 'bg-white/10 text-green-200 hover:bg-white/20' }}">
                        Cat. {{ $cat->nombre }}
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <div class="flex-1 max-w-2xl mx-auto w-full px-4 py-8 space-y-5">
            @if($torneosFinalizados->isEmpty())
                <div class="bg-white/10 rounded-2xl p-10 text-center text-green-200">
                    Aún no hay torneos finalizados.
                </div>
            @else
                @foreach($torneosFinalizados as $torneo)
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden shadow-xl">
                    <div class="bg-white/10 px-5 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-white font-bold">{{ $torneo->nombre }}</p>
                            @if($torneo->fecha_inicio)
                                <p class="text-green-300 text-xs mt-0.5">
                                    {{ \Carbon\Carbon::parse($torneo->fecha_inicio)->format('d/m/Y') }}
                                    @if($torneo->fecha_fin) — {{ \Carbon\Carbon::parse($torneo->fecha_fin)->format('d/m/Y') }} @endif
                                </p>
                            @endif
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-500 text-white shrink-0">Finalizado</span>
                    </div>
                    @php
                        $mastersMostrar = $torneo->masters->where('estado', 'finalizado');
                        $drawsMostrar   = $torneo->draws;
                        if ($filtroCategoriaTorneos) {
                            $mastersMostrar = $mastersMostrar->where('categoria_id', (int)$filtroCategoriaTorneos);
                            $drawsMostrar   = $drawsMostrar->where('categoria_id', (int)$filtroCategoriaTorneos);
                        }
                    @endphp
                    <div class="divide-y divide-white/10">
                        @foreach($mastersMostrar as $master)
                            <div class="px-5 py-3 flex items-center gap-2">
                                <p class="text-white font-semibold text-sm flex-1 min-w-0">
                                    ⭐ Categoría {{ $master->categoria->nombre }}
                                    <span class="text-yellow-300 text-xs font-normal ml-1">Master</span>
                                </p>
                                <a href="{{ route('live.master', [$torneo->id, $master->id]) }}?de={{ $panel }}"
                                   onclick="window.location.replace(this.href); return false;"
                                   class="bg-yellow-400/20 hover:bg-yellow-400/40 border border-yellow-300/40
                                          text-yellow-200 text-xs font-bold px-4 py-2 rounded-lg transition shrink-0">
                                    Ver Master
                                </a>
                            </div>
                        @endforeach
                        @foreach($drawsMostrar as $draw)
                            <div class="px-5 py-3">
                                <p class="text-white font-semibold text-sm mb-2">
                                    Categoría {{ $draw->categoria->nombre }}
                                    <span class="text-yellow-300 text-xs font-normal ml-1">Draw de {{ $draw->tamano }}</span>
                                </p>
                                <div class="flex gap-2">
                                    <a href="{{ route('live.resultados', [$torneo->id, $draw->id]) }}?de={{ $panel }}"
                                       onclick="window.location.replace(this.href); return false;"
                                       class="flex-1 text-center bg-white/15 hover:bg-white/25 border border-white/20
                                              text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                                        📋 Resultados
                                    </a>
                                    <a href="{{ route('live.draw', [$torneo->id, $draw->id]) }}?de={{ $panel }}"
                                       onclick="window.location.replace(this.href); return false;"
                                       class="flex-1 text-center bg-green-500 hover:bg-green-400
                                              text-white text-xs font-bold px-3 py-2 rounded-lg transition shadow">
                                        🎯 Draw
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         PANEL: MIS PARTIDOS
    ═══════════════════════════════════════════════════════ --}}
    @elseif($panel === 'misPartidos')

    <div class="flex flex-col min-h-screen">
        <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
                <button wire:click="cerrar" class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</button>
                <h1 class="text-white font-bold text-lg">🎾 Mis Partidos</h1>
            </div>
        </div>

        <div class="flex-1 max-w-2xl mx-auto w-full px-4 py-6 space-y-5">

            {{-- Buscador de jugador --}}
            @if(!$jugadorSeleccionado)
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4">
                <p class="text-green-200 text-sm mb-3">Buscá tu nombre para ver tus partidos:</p>
                <input wire:model.live.debounce.300ms="busqueda"
                       type="text"
                       placeholder="Escribí tu apellido o nombre..."
                       autocomplete="off"
                       style="width:100%; background:rgba(20,83,45,0.6); border:1px solid #16a34a; color:white; font-size:.875rem; border-radius:.5rem; padding:10px 16px; outline:none; box-sizing:border-box">

                @if(strlen($busqueda) >= 2)
                    @if($jugadores->isEmpty())
                        <p class="text-green-400/70 text-xs mt-3 text-center italic">No se encontraron jugadores.</p>
                    @else
                        <div class="mt-3 space-y-1">
                            @foreach($jugadores as $jug)
                                <button wire:click="seleccionarJugador({{ $jug->id }})"
                                        style="width:100%; text-align:left; padding:10px 16px; border-radius:.75rem; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:white; font-size:.875rem; cursor:pointer; display:block; margin-bottom:4px">
                                    {{ $jug->apellido }}, {{ $jug->nombre }}
                                    <span class="text-green-300 text-xs ml-1">Cat. {{ $jug->categoria->nombre ?? '—' }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @elseif(strlen($busqueda) > 0)
                    <p class="text-yellow-300 text-xs mt-2">Escribí al menos 2 caracteres.</p>
                @endif
            </div>
            @else

            {{-- Jugador seleccionado --}}
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white font-bold text-base">{{ $jugadorSeleccionado->apellido }}, {{ $jugadorSeleccionado->nombre }}</p>
                    <p class="text-green-300 text-xs">Cat. {{ $jugadorSeleccionado->categoria->nombre ?? '—' }}</p>
                </div>
                <button wire:click="$set('jugadorId', null)"
                        class="text-green-300 hover:text-white text-sm font-semibold transition border border-green-500/70
                               rounded-lg px-4 py-2">
                    Cambiar jugador
                </button>
            </div>

            {{-- Filtros: año --}}
            @if($misAniosJugador->isNotEmpty())
            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                <button wire:click="$set('misAnio', '')"
                        class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold transition
                               {{ $misAnio === '' ? 'bg-white text-green-900' : 'bg-white/15 text-white hover:bg-white/25' }}">
                    Todos
                </button>
                @foreach($misAniosJugador as $anio)
                <button wire:click="$set('misAnio', '{{ $anio }}')"
                        class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold transition
                               {{ (string)$misAnio === (string)$anio ? 'bg-white text-green-900' : 'bg-white/15 text-white hover:bg-white/25' }}">
                    {{ $anio }}
                </button>
                @endforeach
            </div>
            @endif

            {{-- Filtros: torneo --}}
            @if($misTorneosJugador->count() > 1)
            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                <button wire:click="$set('misTorneoId', null)"
                        class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition
                               {{ $misTorneoId === null ? 'bg-yellow-300 text-green-900' : 'bg-white/10 text-green-200 hover:bg-white/20' }}">
                    Todos
                </button>
                @foreach($misTorneosJugador as $t)
                <button wire:click="$set('misTorneoId', {{ $t['id'] }})"
                        class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition
                               {{ $misTorneoId === $t['id'] ? 'bg-yellow-300 text-green-900' : 'bg-white/10 text-green-200 hover:bg-white/20' }}">
                    {{ $t['nombre'] }}
                </button>
                @endforeach
            </div>
            @endif

            @if(empty($misPartidosPorAnio))
                <div class="bg-white/10 rounded-2xl p-8 text-center text-green-200">
                    No hay partidos registrados para este jugador.
                </div>
            @else
                @foreach($misPartidosPorAnio as $anio => $partidos)
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px flex-1 bg-white/20"></div>
                        <span class="text-white font-bold text-sm uppercase tracking-wider px-4 py-1
                                     rounded-full bg-white/15">{{ $anio }}</span>
                        <div class="h-px flex-1 bg-white/20"></div>
                    </div>

                    <div class="space-y-2">
                        @foreach($partidos as $partido)
                        <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-3
                                    {{ $partido['gano'] ? 'border-green-400/40' : 'border-red-400/20' }}">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="min-w-0">
                                    <p class="text-white text-xs font-semibold truncate">{{ $partido['torneo'] }}</p>
                                    <p class="text-yellow-300 text-xs">Cat. {{ $partido['categoria'] }} — {{ $partido['ronda'] }}</p>
                                </div>
                                @if($partido['gano'])
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0 bg-green-500/30 text-green-300">
                                    Ganó
                                </span>
                                @else
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0"
                                      style="background:rgba(220,38,38,0.35); color:#fca5a5">
                                    Perdió
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-white/60 text-sm">
                                    vs
                                    @if($partido['rival'])
                                        <span class="text-white/80">{{ $partido['rival']->apellido }}, {{ $partido['rival']->nombre }}</span>
                                    @else
                                        <span class="italic">—</span>
                                    @endif
                                </p>
                                @if($partido['resultado'])
                                    <span class="text-green-200 font-mono text-sm font-bold shrink-0">{{ $partido['resultado'] }}</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            @endif

            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         PANEL: INFORMACIÓN
    ═══════════════════════════════════════════════════════ --}}
    @elseif($panel === 'info')

    <div class="flex flex-col min-h-screen">
        <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
                <button wire:click="cerrar" class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</button>
                <h1 class="text-white font-bold text-lg">ℹ️ Información</h1>
            </div>
        </div>

        <div class="flex-1 max-w-2xl mx-auto w-full px-4 py-6">
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 shadow-xl">
                <div class="text-yellow-200 text-sm leading-relaxed whitespace-pre-wrap">{{ $panelInfo }}</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         PANTALLA PRINCIPAL
    ═══════════════════════════════════════════════════════ --}}
    @else

    <div class="flex-shrink-0">
        <div class="flex justify-end px-4 pt-3">
            <button onclick="window.close()"
                    class="bg-white text-green-900 font-semibold text-sm px-4 py-1.5 rounded-lg shadow hover:bg-green-50 transition">
                Salir
            </button>
        </div>
        <div class="text-center pt-2 pb-5 px-4">
            <div class="text-5xl mb-2">🎾</div>
            <h1 class="text-4xl font-extrabold text-white tracking-tight drop-shadow-xl leading-tight">
                {{ $clubNombre }}
            </h1>
            @if($clubCiudad)
                <p class="text-yellow-200 text-base mt-1.5 font-medium">{{ $clubCiudad }}</p>
            @endif
            <p class="text-green-300 text-sm mt-1 tracking-widest uppercase font-semibold">Gestión de torneos</p>
        </div>
    </div>

    <div class="max-w-2xl mx-auto w-full px-3 pb-8">
        <div class="bg-white/10 backdrop-blur-sm border border-white/25 rounded-2xl overflow-hidden shadow-2xl">

            <div class="bg-gradient-to-r from-white/15 to-transparent px-5 py-4 flex items-center gap-3 border-b border-white/15">
                <span class="text-3xl">📺</span>
                <div>
                    <h2 class="text-white font-extrabold text-xl leading-tight">Panel Online</h2>
                    <p class="text-green-300 text-xs mt-0.5 tracking-wide">Seguí el torneo en tiempo real</p>
                </div>
            </div>

            @if($torneos->isNotEmpty())
            <div class="px-4 pt-4 pb-3 space-y-2">
                <div class="flex items-center gap-3 mb-1">
                    <div class="h-px flex-1 bg-white/20"></div>
                    <span class="text-green-300 text-xs font-semibold uppercase tracking-widest">Torneos activos</span>
                    <div class="h-px flex-1 bg-white/20"></div>
                </div>
                @foreach($torneos as $torneo)
                    <div class="border border-white/20 rounded-xl overflow-hidden">
                        <div class="bg-white/10 px-3 py-2.5 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-white font-bold text-sm truncate">{{ $torneo->nombre }}</p>
                                @if($torneo->fecha_inicio)
                                    <p class="text-green-300 text-xs">
                                        Fecha de inicio: {{ \Carbon\Carbon::parse($torneo->fecha_inicio)->format('d/m/Y') }}
                                        @if($torneo->fecha_fin) — {{ \Carbon\Carbon::parse($torneo->fecha_fin)->format('d/m/Y') }} @endif
                                    </p>
                                @endif
                            </div>
                            @php $tieneCampeon = $torneo->draws->contains(fn($d) => $d->partidos->isNotEmpty()); @endphp
                            @if($tieneCampeon)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-500 text-white shrink-0">Finalizado</span>
                            @elseif($torneo->estado === 'activo')
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-500 text-white shrink-0">En curso</span>
                            @endif
                        </div>
                        @if(($torneo->tipo ?? 'normal') === 'master')
                            @if($torneo->masters->isEmpty())
                                <div class="px-3 py-2 text-yellow-300/60 text-xs italic">⭐ Master — Sin categorías configuradas aún.</div>
                            @else
                                <div class="divide-y divide-white/10">
                                    @foreach($torneo->masters as $master)
                                        <div class="px-3 py-2 flex items-center gap-2">
                                            <p class="text-white text-xs font-semibold flex-1 min-w-0 truncate">
                                                ⭐ Cat. {{ $master->categoria->nombre }}
                                                <span class="text-yellow-300/70 font-normal">Master</span>
                                            </p>
                                            <a href="{{ route('live.master', [$torneo->id, $master->id]) }}?de="
                                               onclick="window.location.replace(this.href); return false;"
                                               class="bg-yellow-400/20 hover:bg-yellow-400/40 border border-yellow-300/40 text-yellow-200 text-xs font-bold px-4 py-2 rounded-lg transition shrink-0">
                                                Ver Master
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @elseif($torneo->draws->isEmpty())
                            <div class="px-3 py-2 text-green-300/60 text-xs italic">Draw no disponible aún.</div>
                        @else
                            <div class="divide-y divide-white/10">
                                @foreach($torneo->draws as $draw)
                                    <div class="px-3 py-2 flex items-center gap-2">
                                        <p class="text-white text-xs font-semibold flex-1 min-w-0 truncate">Cat. {{ $draw->categoria->nombre }}</p>
                                        <div class="flex gap-1.5 shrink-0">
                                            <a href="{{ route('live.resultados', [$torneo->id, $draw->id]) }}?de="
                                               onclick="window.location.replace(this.href); return false;"
                                               class="bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                                📋 Resultados
                                            </a>
                                            <a href="{{ route('live.draw', [$torneo->id, $draw->id]) }}?de="
                                               onclick="window.location.replace(this.href); return false;"
                                               class="bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                                🎯 Draw
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif

            <div class="px-4 pt-4 pb-3 grid grid-cols-2 gap-3">
                <button wire:click="abrirRanking"
                        class="flex flex-col items-center gap-2 bg-yellow-400/20 border border-yellow-300/40
                               rounded-xl px-3 py-5 hover:bg-yellow-400/35 transition text-center w-full">
                    <span class="text-4xl">🏆</span>
                    <div>
                        <p class="text-white font-bold text-sm leading-tight">Ranking General</p>
                        <p class="text-yellow-200 text-xs leading-tight mt-0.5">Posiciones por categoría</p>
                    </div>
                </button>
                <button wire:click="abrirTorneos"
                        class="flex flex-col items-center gap-2 bg-white/15 border border-white/25
                               rounded-xl px-3 py-5 hover:bg-white/25 transition text-center w-full">
                    <span class="text-4xl">🏅</span>
                    <div>
                        <p class="text-white font-bold text-sm leading-tight">Torneos finalizados</p>
                        <p class="text-green-200 text-xs leading-tight mt-0.5">Resultados y draws</p>
                    </div>
                </button>
                <button wire:click="abrirMisPartidos"
                        style="grid-column:1/-1; background:rgba(99,179,237,0.15); border:1px solid rgba(144,205,244,0.35); border-radius:0.75rem; padding:18px 16px; text-align:center; width:100%; display:flex; flex-direction:column; align-items:center; gap:8px; cursor:pointer; transition:background .15s">
                    <span style="font-size:2.25rem">🎾</span>
                    <div>
                        <p class="text-white font-bold text-sm leading-tight">Mis Partidos</p>
                        <p style="color:rgba(147,197,253,0.85); font-size:.75rem; margin-top:2px">Seguí tu estadística personal</p>
                    </div>
                </button>
                @if($panelInfo)
                <button wire:click="abrirInfo"
                        style="grid-column:1/-1; background:rgba(251,191,36,0.12); border:1px solid rgba(252,211,77,0.35); border-radius:0.75rem; padding:18px 16px; text-align:center; width:100%; display:flex; flex-direction:column; align-items:center; gap:8px; cursor:pointer; transition:background .15s">
                    <span style="font-size:2.25rem">ℹ️</span>
                    <div>
                        <p class="text-white font-bold text-sm leading-tight">Información</p>
                        <p style="color:rgba(253,230,138,0.85); font-size:.75rem; margin-top:2px">Novedades y avisos</p>
                    </div>
                </button>
                @endif
            </div>


            <div class="px-4 pt-1 pb-4 border-t border-white/10">
                <div class="text-center">
                    <a href="{{ route('admin.login') }}" wire:navigate
                       class="text-white/40 hover:text-white/70 text-xs transition">
                        🔐 Panel Administrador → Ingreso al panel
                    </a>
                </div>
            </div>

        </div>
    </div>

    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', bindScrollHints);
document.addEventListener('livewire:updated', bindScrollHints);

function bindScrollHints() {
    document.querySelectorAll('.scroll-table').forEach(function(table) {
        var hint = table.closest('div')?.previousElementSibling?.querySelector('.scroll-hint');
        if (!hint) return;
        function update() {
            hint.style.visibility = (table.scrollLeft + table.clientWidth >= table.scrollWidth - 4) ? 'hidden' : 'visible';
        }
        update();
        table.removeEventListener('scroll', update);
        table.addEventListener('scroll', update);
    });
}
</script>
