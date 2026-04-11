<div wire:poll.30s class="flex flex-col"
     style="min-height:100dvh; background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('{{ asset('canchatenis.png') }}') center/cover fixed;">

    {{-- ═══════════════════════════════════════════════════════
         IFRAME OVERLAY (Draw / Resultados / Ver Master)
         Carga en su propio contexto — el historial del padre
         nunca cambia, Salir siempre funciona.
    ═══════════════════════════════════════════════════════ --}}
    @if($iframeUrl)
    <div wire:key="iframe-{{ md5($iframeUrl) }}" class="fixed inset-0 z-50 flex flex-col bg-green-950">
        <div class="flex items-center justify-between px-4 py-2.5 bg-green-900/95 border-b border-green-700/50 shrink-0">
            <button wire:click="cerrarIframe"
                    class="text-green-400 hover:text-white transition text-sm font-semibold">
                ← Volver
            </button>
            <button onclick="window.close()"
                    class="bg-white text-green-900 font-semibold text-xs px-3 py-1.5 rounded-lg shadow">
                Salir
            </button>
        </div>
        <iframe src="{{ $iframeUrl }}"
                class="flex-1 w-full border-0 bg-green-950"
                style="height: calc(100dvh - 48px)">
        </iframe>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         PANEL: RANKING
    ═══════════════════════════════════════════════════════ --}}
    @if($panel === 'ranking')

    <div class="flex flex-col min-h-screen">
        <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
            <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button wire:click="cerrar" class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</button>
                    <h1 class="text-white font-bold text-lg">🏆 Ranking General</h1>
                </div>
                <select wire:model.live="filtroCategoria"
                        class="bg-green-800 border border-green-600 text-white text-sm rounded-lg px-3 py-1.5
                               focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
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
            <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
                <button wire:click="cerrar" class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</button>
                <h1 class="text-white font-bold text-lg">🏅 Torneos finalizados</h1>
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
                    <div class="divide-y divide-white/10">
                        @foreach($torneo->masters->where('estado', 'finalizado') as $master)
                            <div class="px-5 py-3 flex items-center gap-2">
                                <p class="text-white font-semibold text-sm flex-1 min-w-0">
                                    ⭐ Categoría {{ $master->categoria->nombre }}
                                    <span class="text-yellow-300 text-xs font-normal ml-1">Master</span>
                                </p>
                                <button wire:click="abrirIframe('{{ route('live.master', [$torneo->id, $master->id]) }}')"
                                        class="bg-yellow-400/20 hover:bg-yellow-400/40 border border-yellow-300/40
                                               text-yellow-200 text-xs font-bold px-4 py-2 rounded-lg transition shrink-0">
                                    Ver Master
                                </button>
                            </div>
                        @endforeach
                        @foreach($torneo->draws as $draw)
                            <div class="px-5 py-3">
                                <p class="text-white font-semibold text-sm mb-2">
                                    Categoría {{ $draw->categoria->nombre }}
                                    <span class="text-yellow-300 text-xs font-normal ml-1">Draw de {{ $draw->tamano }}</span>
                                </p>
                                <div class="flex gap-2">
                                    <button wire:click="abrirIframe('{{ route('live.resultados', [$torneo->id, $draw->id]) }}')"
                                            class="flex-1 text-center bg-white/15 hover:bg-white/25 border border-white/20
                                                   text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                                        📋 Resultados
                                    </button>
                                    <button wire:click="abrirIframe('{{ route('live.draw', [$torneo->id, $draw->id]) }}')"
                                            class="flex-1 text-center bg-green-500 hover:bg-green-400
                                                   text-white text-xs font-bold px-3 py-2 rounded-lg transition shadow">
                                        🎯 Draw
                                    </button>
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
                       class="w-full bg-green-800/60 border border-green-600 text-white placeholder-green-400
                              text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-400">

                @if(strlen($busqueda) >= 2)
                    @if($jugadores->isEmpty())
                        <p class="text-green-400/70 text-xs mt-3 text-center italic">No se encontraron jugadores.</p>
                    @else
                        <div class="mt-3 space-y-1">
                            @foreach($jugadores as $jug)
                                <button wire:click="seleccionarJugador({{ $jug->id }})"
                                        class="w-full text-left px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20
                                               border border-white/15 text-white text-sm transition">
                                    {{ $jug->apellido }}, {{ $jug->nombre }}
                                    <span class="text-green-300 text-xs ml-1">Cat. {{ $jug->categoria->nombre ?? '—' }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @elseif(strlen($busqueda) > 0)
                    <p class="text-green-400/70 text-xs mt-2">Escribí al menos 2 caracteres.</p>
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
                        class="text-green-400 hover:text-white text-xs transition border border-green-600/50
                               rounded-lg px-3 py-1.5">
                    Cambiar jugador
                </button>
            </div>

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
                                    <p class="text-white/80 text-xs font-semibold truncate">{{ $partido['torneo'] }}</p>
                                    <p class="text-green-300 text-xs">Cat. {{ $partido['categoria'] }} — {{ $partido['ronda'] }}</p>
                                </div>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0
                                             {{ $partido['gano'] ? 'bg-green-500/30 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                                    {{ $partido['gano'] ? 'Ganó' : 'Perdió' }}
                                </span>
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
         PANTALLA PRINCIPAL
    ═══════════════════════════════════════════════════════ --}}
    @else

    <div class="flex justify-end px-4 pt-3 flex-shrink-0">
        <button onclick="window.close()"
                class="bg-white text-green-900 font-semibold text-sm px-4 py-1.5 rounded-lg shadow hover:bg-green-50 transition">
            Salir
        </button>
    </div>

    <div class="text-center pt-2 pb-2 px-4 flex-shrink-0">
        <div class="text-3xl mb-1">🎾</div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight drop-shadow-lg leading-tight">
            {{ $clubNombre }}
        </h1>
        @if($clubCiudad)
            <p class="text-yellow-200 text-sm">{{ $clubCiudad }}</p>
        @endif
        <p class="text-green-300 text-xs mt-0.5 tracking-wide">Gestión de torneos</p>
    </div>

    <div class="max-w-2xl mx-auto w-full px-3 pb-6">
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden shadow-xl">

            <div class="bg-white/10 px-4 py-2.5 flex items-center gap-2 border-b border-white/10">
                <span class="text-lg">📺</span>
                <h2 class="text-white font-bold text-base">Panel Online</h2>
            </div>

            <div class="px-3 pt-3 pb-2 grid grid-cols-2 gap-2">
                <button wire:click="abrirRanking"
                        class="flex items-center gap-2 bg-yellow-400/20 border border-yellow-300/30
                               rounded-xl px-3 py-2.5 hover:bg-yellow-400/30 transition text-left w-full">
                    <span class="text-xl shrink-0">🏆</span>
                    <div class="min-w-0">
                        <p class="text-white font-semibold text-xs leading-tight">Ranking General</p>
                        <p class="text-yellow-200 text-xs leading-tight">Por categoría</p>
                    </div>
                </button>
                <button wire:click="abrirTorneos"
                        class="flex items-center gap-2 bg-white/15 border border-white/20
                               rounded-xl px-3 py-2.5 hover:bg-white/25 transition text-left w-full">
                    <span class="text-xl shrink-0">🏅</span>
                    <div class="min-w-0">
                        <p class="text-white font-semibold text-xs leading-tight">Torneos finalizados</p>
                        <p class="text-green-200 text-xs leading-tight">Resultados y draws</p>
                    </div>
                </button>
                <button wire:click="abrirMisPartidos"
                        class="col-span-2 flex items-center gap-2 bg-blue-400/20 border border-blue-300/30
                               rounded-xl px-3 py-2.5 hover:bg-blue-400/30 transition text-left w-full">
                    <span class="text-xl shrink-0">🎾</span>
                    <div class="min-w-0">
                        <p class="text-white font-semibold text-xs leading-tight">Mis Partidos</p>
                        <p class="text-blue-200 text-xs leading-tight">Buscá tus partidos por año</p>
                    </div>
                </button>
            </div>

            <div class="px-3 pb-3 pt-1 space-y-2">
                @if($torneos->isEmpty())
                    <div class="text-green-200/70 text-xs italic text-center py-3">
                        No hay torneos disponibles por el momento.
                    </div>
                @else
                    @foreach($torneos as $torneo)
                        <div class="border border-red-500 rounded-xl overflow-hidden">
                            <div class="bg-white/10 px-3 py-2 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-white font-bold text-xs truncate">{{ $torneo->nombre }}</p>
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
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-500 text-white shrink-0">Torneo en curso</span>
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
                                                <button wire:click="abrirIframe('{{ route('live.master', [$torneo->id, $master->id]) }}')"
                                                        class="bg-yellow-400/20 hover:bg-yellow-400/40 border border-yellow-300/40 text-yellow-200 text-xs font-bold px-4 py-2 rounded-lg transition shrink-0">
                                                    Ver Master
                                                </button>
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
                                                <button wire:click="abrirIframe('{{ route('live.resultados', [$torneo->id, $draw->id]) }}')"
                                                        class="bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                                    📋 Resultados
                                                </button>
                                                <button wire:click="abrirIframe('{{ route('live.draw', [$torneo->id, $draw->id]) }}')"
                                                        class="bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                                    🎯 Draw
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="px-3 pt-1 pb-3 border-t border-white/10">
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
