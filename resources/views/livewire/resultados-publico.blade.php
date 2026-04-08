<div wire:poll.20s class="min-h-screen flex flex-col"
     style="background: linear-gradient(160deg, #166534 0%, #15803d 40%, #16a34a 70%, #22c55e 100%)">

    {{-- Header --}}
    <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
        <div class="max-w-3xl mx-auto px-4 md:px-8 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="#" onclick="history.back(); return false;"
                   class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</a>
                <div class="min-w-0">
                    <h1 class="text-white font-bold text-base md:text-lg truncate">
                        🎾 {{ $torneo->nombre }}
                    </h1>
                    <p class="text-green-400 text-xs">
                        Resultados — Categoría {{ $draw->categoria->nombre }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('live.draw', [$torneo->id, $draw->id]) }}" wire:navigate
                   class="text-green-300 hover:text-white text-xs transition underline underline-offset-2">
                    Ver Draw →
                </a>
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                </span>
            </div>
        </div>
    </div>

    {{-- Contenido --}}
    <div class="flex-1 max-w-3xl mx-auto w-full px-4 md:px-8 py-8 space-y-6">

        @if($partidos->isEmpty())
            <div class="bg-white/10 rounded-2xl p-10 text-center">
                <p class="text-white/60 text-lg italic">Aún no hay resultados cargados.</p>
            </div>
        @else
            @foreach($partidos->sortKeysDesc(SORT_NUMERIC) as $ronda => $matches)
                <div>
                    {{-- Cabecera de ronda --}}
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px flex-1 bg-white/20"></div>
                        <span class="text-white font-bold text-xs uppercase tracking-wider px-4 py-1 rounded-full
                                     {{ (int)$ronda === 1 ? 'bg-yellow-400/30 border border-yellow-300/50 text-yellow-200' : 'bg-white/15' }}">
                            {{ $nombreRonda((int)$ronda, (int)$draw->tamano) }}
                        </span>
                        <div class="h-px flex-1 bg-white/20"></div>
                    </div>

                    {{-- Partidos --}}
                    <div class="space-y-2">
                        @foreach($matches as $partido)
                            <div class="bg-white/10 border border-white/20 rounded-xl px-5 py-3
                                        flex items-center justify-between gap-4
                                        {{ (int)$ronda === 1 ? 'border-yellow-300/30' : '' }}">

                                {{-- Jugadores (ganador siempre primero) --}}
                                @php
                                    $ganador  = $partido->ganador_id == $partido->jugador1_id ? $partido->jugador1 : $partido->jugador2;
                                    $perdedor = $partido->ganador_id == $partido->jugador1_id ? $partido->jugador2 : $partido->jugador1;
                                @endphp
                                <div class="flex items-center gap-3 text-sm min-w-0">
                                    <span class="text-white font-bold truncate">
                                        {{ $ganador->apellido }}, {{ $ganador->nombre }}
                                    </span>
                                    <span class="text-white/30 shrink-0">vs</span>
                                    <span class="text-white/50 truncate">
                                        {{ $perdedor->apellido }}, {{ $perdedor->nombre }}
                                    </span>
                                </div>

                                {{-- Resultado --}}
                                <span class="text-white font-bold text-base tracking-wider shrink-0">
                                    {{ $partido->resultado }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Footer --}}
    <div class="text-center pb-5 text-green-700 text-xs">
        Se actualiza automáticamente cada 20 segundos
    </div>

</div>
