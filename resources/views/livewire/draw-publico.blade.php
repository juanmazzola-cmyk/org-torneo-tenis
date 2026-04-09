<div wire:poll.20s class="min-h-screen flex flex-col bg-white">

    {{-- Header --}}
    <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
        <div class="max-w-full px-4 md:px-8 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('live.resultados', [$torneo->id, $draw->id]) }}"
                   class="text-green-300 hover:text-white text-xs transition underline underline-offset-2 shrink-0">
                    📋 Resultados
                </a>
                <div class="min-w-0">
                    <h1 class="text-white font-bold text-base md:text-lg truncate">
                        🎾 {{ $torneo->nombre }}
                    </h1>
                    <p class="text-green-400 text-xs">
                        Categoría {{ $draw->categoria->nombre }} — Draw de {{ $draw->tamano }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                </span>
                <span class="text-green-400 text-xs font-medium uppercase tracking-wider hidden sm:inline">En vivo</span>
            </div>
        </div>
    </div>

    {{-- Bracket --}}
    @php
        $roundsTemp  = $partidos->sortKeysDesc(SORT_NUMERIC);
        $totalRoundsTemp = count($roundsTemp);
        $bracketW = ($totalRoundsTemp * 200) + (($totalRoundsTemp - 1) * 40) + 64;
    @endphp
    <div class="flex-1" x-data="{ ajustado: true }" x-init="
        const b = document.getElementById('bracket-inner');
        if (b) {
            const escala = (window.innerWidth - 16) / {{ $bracketW }};
            b.style.transform = 'scale(' + escala + ')';
            b.style.transformOrigin = 'top left';
            b.parentElement.style.height = (b.offsetHeight * escala) + 'px';
        }
    " style="touch-action: pan-x pan-y pinch-zoom;">
        <div class="px-4 pt-2 pb-1">
            <button @click="
                ajustado = !ajustado;
                const b = document.getElementById('bracket-inner');
                if (ajustado) {
                    const escala = (window.innerWidth - 16) / {{ $bracketW }};
                    b.style.transform = 'scale(' + escala + ')';
                    b.style.transformOrigin = 'top left';
                    b.parentElement.style.height = (b.offsetHeight * escala) + 'px';
                } else {
                    b.style.transform = '';
                    b.parentElement.style.height = '';
                }
            " type="button"
                class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-3 py-1.5 rounded-lg transition">
                <span x-text="ajustado ? '🔍 Tamaño normal' : '🗺 Ver completo'"></span>
            </button>
        </div>
        <div class="overflow-x-auto">
        <div id="bracket-inner" class="p-4 md:p-8" style="transform-origin: top left;">

        @php
            $rounds      = $partidos->sortKeysDesc(SORT_NUMERIC);
            $roundsArr   = $rounds->values()->all();
            $roundKeys   = $rounds->keys()->all();
            $totalRounds = count($roundsArr);
            $nFirstRound = count($roundsArr[0]);
            $slotHeight  = 110;
            $containerH  = $nFirstRound * $slotHeight;
            // Ancho real del bracket: rondas * 200px + conectores * 40px + padding 64px
            $bracketW    = ($totalRounds * 200) + (($totalRounds - 1) * 40) + 64;

            $nombreRonda = function(int $ronda, int $tamano) {
                return match($ronda) {
                    32      => '32avos',
                    16      => '16avos',
                    8       => 'Octavos',
                    4       => 'Cuartos',
                    2       => 'Semifinal',
                    1       => 'Final',
                    default => 'Ronda ' . $ronda,
                };
            };
        @endphp

        {{-- Cabecera de rondas --}}
        <div class="flex mb-3" style="gap:0">
            @foreach($roundsArr as $ri => $rMatches)
                <div style="width:200px; flex-shrink:0" class="text-center">
                    <span class="text-xs font-bold text-green-700 uppercase tracking-wider">
                        {{ $nombreRonda((int)$roundKeys[$ri], (int)$draw->tamano) }}
                    </span>
                </div>
                @if($ri < $totalRounds - 1)
                    <div style="width:40px; flex-shrink:0"></div>
                @endif
            @endforeach
        </div>

        {{-- Bracket --}}
        <div class="flex items-stretch" style="height:{{ $containerH }}px; gap:0">

            @foreach($roundsArr as $ri => $rMatches)

                {{-- Columna de ronda --}}
                <div class="flex flex-col" style="width:200px; flex-shrink:0">
                    @foreach($rMatches as $partido)
                        <div class="flex-1 flex items-center" style="padding:4px 0">
                            <div class="w-full rounded-xl overflow-hidden shadow border
                                {{ $partido->ganador_id ? 'border-green-400' : 'border-gray-200' }}
                                bg-white">

                                {{-- Jugador 1 --}}
                                <div class="px-3 py-2 text-sm border-b border-gray-100 flex items-center gap-1
                                    {{ $partido->ganador_id == $partido->jugador1_id
                                        ? 'bg-green-100 font-bold text-green-800'
                                        : ($partido->ganador_id
                                            ? 'text-gray-400'
                                            : 'text-gray-800') }}">
                                    @if($partido->jugador1)
                                        {{ $partido->jugador1->apellido }}, {{ $partido->jugador1->nombre }}
                                    @else
                                        <span class="text-gray-300 italic text-xs">Por definir</span>
                                    @endif
                                </div>

                                {{-- Jugador 2 --}}
                                <div class="px-3 py-2 text-sm border-b border-gray-100 flex items-center gap-1
                                    {{ $partido->ganador_id == $partido->jugador2_id
                                        ? 'bg-green-100 font-bold text-green-800'
                                        : ($partido->ganador_id
                                            ? 'text-gray-400'
                                            : 'text-gray-800') }}">
                                    @if($partido->jugador2)
                                        {{ $partido->jugador2->apellido }}, {{ $partido->jugador2->nombre }}
                                    @else
                                        <span class="text-gray-300 italic text-xs">Por definir</span>
                                    @endif
                                </div>

                                {{-- Resultado --}}
                                <div class="px-3 py-1 bg-gray-50 text-xs font-bold text-green-700 text-right min-h-[22px]">
                                    {{ $partido->resultado ?? '' }}
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Conectores --}}
                @if($ri < $totalRounds - 1)
                    @php $nConn = count($rMatches) / 2; @endphp
                    <div class="flex flex-col" style="width:40px; flex-shrink:0">
                        @for($c = 0; $c < $nConn; $c++)
                            <div class="flex-1 relative" style="min-height:0">
                                <div style="position:absolute; top:25%; left:0; width:20px; height:2px; background:#4ade80; transform:translateY(-50%)"></div>
                                <div style="position:absolute; top:75%; left:0; width:20px; height:2px; background:#4ade80; transform:translateY(-50%)"></div>
                                <div style="position:absolute; left:20px; top:25%; bottom:25%; width:2px; background:#4ade80"></div>
                                <div style="position:absolute; top:50%; left:20px; width:20px; height:2px; background:#4ade80; transform:translateY(-50%)"></div>
                            </div>
                        @endfor
                    </div>
                @endif

            @endforeach
        </div>
        </div>{{-- cierre bracket-inner --}}
        </div>{{-- cierre overflow-x-auto --}}
    </div>

    {{-- Footer --}}
    <div class="text-center pb-5 text-gray-400 text-xs">
        Se actualiza automáticamente cada 20 segundos
    </div>

</div>
