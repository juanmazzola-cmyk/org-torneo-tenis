<div style="min-height:100dvh; background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('{{ asset('canchatenis.png') }}') center/cover fixed;">

    <!-- Header -->
    <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
        <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
            <a href="{{ request()->query('de') !== null ? '/?panel='.request()->query('de').'&anio='.request()->query('anio', '') : '/' }}"
               onclick="window.location.replace(this.href); return false;"
               class="text-green-400 hover:text-white transition text-sm font-semibold shrink-0">
                ← Volver
            </a>
            <div class="min-w-0">
                <h1 class="text-white font-bold text-base truncate">⭐ {{ $master->torneo->nombre }}</h1>
                <p class="text-yellow-300 text-xs">{{ $dobles ? 'Dobles' : 'Master' }} — Categoría {{ $master->categoria->nombre }}</p>
            </div>
        </div>
    </div>

    <div class="px-3 py-4">

    <div class="max-w-2xl mx-auto space-y-4">

        <!-- Zonas -->
        @foreach($grupos as $grupo)
        @php
            $jugadoresGrupo = $grupo->jugadoresGrupo->keyBy('posicion');
            $partidos       = $grupo->partidos->groupBy('jornada');
            $pos            = $posiciones[$grupo->id] ?? [];
            $hayPartidos    = $grupo->partidos->whereNotNull('ganador_id')->isNotEmpty();
            $descansos      = $dobles && $grupo->partidos->count() === 3 ? $grupo->descansos() : [];
        @endphp

        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden">
            <div class="bg-white/10 px-4 py-2.5 border-b border-white/10">
                <h2 class="text-white font-bold text-base">{{ $grupo->nombre }}</h2>
            </div>

            <!-- Partidos -->
            <div class="px-4 pt-3 pb-2">
                <p class="text-green-300 text-xs font-semibold uppercase tracking-wide mb-2">Resultados</p>
                @foreach([1,2,3] as $jornada)
                @php $matchesJornada = $partidos->get($jornada, collect()); @endphp
                @if($matchesJornada->isNotEmpty())
                <div class="mb-2">
                    <p class="text-white/50 text-xs mb-1">Jornada {{ $jornada }}</p>
                    @if(isset($descansos[$jornada]))
                    <p class="text-white/40 text-xs italic mb-1">Descansa: {{ $grupo->jugadoresGrupo->firstWhere('jugador_id', $descansos[$jornada])?->jugador->apellido }}</p>
                    @endif
                    @foreach($matchesJornada as $partido)
                    @if($partido->jugador1_id && $partido->jugador2_id)
                    <div class="flex items-center justify-between py-1.5 border-b border-white/10 last:border-0">
                        <div class="flex-1 text-sm">
                            <span class="{{ $partido->ganador_id == $partido->jugador1_id ? 'font-bold text-white' : 'text-white/60' }}">
                                {{ $partido->jugador1->apellido }}
                            </span>
                            <span class="text-white/40 mx-1 text-xs">vs</span>
                            <span class="{{ $partido->ganador_id == $partido->jugador2_id ? 'font-bold text-white' : 'text-white/60' }}">
                                {{ $partido->jugador2->apellido }}
                            </span>
                        </div>
                        @if($partido->resultado)
                            <span class="text-xs text-green-300 font-mono ml-2 shrink-0">{{ $partido->resultado }}</span>
                        @else
                            <span class="text-xs text-white/30 ml-2 shrink-0 italic">Pendiente</span>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>
                @endif
                @endforeach
                @if($grupo->partidos->isEmpty())
                    <p class="text-white/40 text-xs italic py-1">Sin partidos generados aún.</p>
                @endif
            </div>

            <!-- Tabla de posiciones -->
            @if(!empty($pos))
            <div class="px-4 pb-4">
                <p class="text-green-300 text-xs font-semibold uppercase tracking-wide mb-2">Posiciones</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-white/10 text-white/70">
                                <th class="px-1 py-1.5 text-left">#</th>
                                <th class="px-2 py-1.5 text-left">Jugador</th>
                                <th class="px-1 py-1.5 text-center">PJ</th>
                                <th class="px-1 py-1.5 text-center">PG</th>
                                <th class="px-1 py-1.5 text-center">PP</th>
                                <th class="px-1 py-1.5 text-center">DIF</th>
                                <th class="px-1 py-1.5 text-center">DS</th>
                                <th class="px-1 py-1.5 text-center">DG</th>
                                <th class="px-1 py-1.5 text-center font-bold text-white">PTS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pos as $i => $s)
                            @php
                                // En dobles el ✓ marca a los que clasifican de verdad según la cantidad de zonas
                                $clasifica = $dobles ? in_array($s['jugador']->id, $clasificacion['marcados']) : $i < 2;
                            @endphp
                            <tr class="{{ $s['retirado'] ? 'opacity-60' : ($clasifica ? 'bg-white/10' : '') }} border-t border-white/10">
                                <td class="px-1 py-1.5 text-white/60 font-bold">
                                    {{ $s['retirado'] ? 'R' : ($clasifica ? '✓' : ($i + 1)) }}
                                </td>
                                <td class="px-2 py-1.5 font-semibold text-white {{ $dobles ? 'leading-tight' : 'whitespace-nowrap' }}">
                                    {{ $s['jugador']->apellido }}
                                    @if($s['retirado'])
                                        <span class="text-xs bg-red-500/30 text-red-200 px-1.5 py-0.5 rounded font-medium">Retirado</span>
                                    @endif
                                </td>
                                <td class="px-1 py-1.5 text-center text-white/70">{{ $s['pj'] }}</td>
                                <td class="px-1 py-1.5 text-center text-white/70">{{ $s['pg'] }}</td>
                                <td class="px-1 py-1.5 text-center text-white/70">{{ $s['pp'] }}</td>
                                <td class="px-1 py-1.5 text-center {{ $s['dif_part'] > 0 ? 'text-green-400' : ($s['dif_part'] < 0 ? 'text-red-400' : 'text-white/50') }}">
                                    {{ $s['dif_part'] > 0 ? '+' : '' }}{{ $s['dif_part'] }}
                                </td>
                                <td class="px-1 py-1.5 text-center {{ $s['dif_set'] > 0 ? 'text-green-400' : ($s['dif_set'] < 0 ? 'text-red-400' : 'text-white/50') }}">
                                    {{ $s['dif_set'] > 0 ? '+' : '' }}{{ $s['dif_set'] }}
                                </td>
                                <td class="px-1 py-1.5 text-center {{ $s['dif_game'] > 0 ? 'text-green-400' : ($s['dif_game'] < 0 ? 'text-red-400' : 'text-white/50') }}">
                                    {{ $s['dif_game'] > 0 ? '+' : '' }}{{ $s['dif_game'] }}
                                </td>
                                <td class="px-1 py-1.5 text-center font-bold text-yellow-300">{{ $s['puntos'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($dobles && $grupos->count() === 3)
                <p class="text-white/40 text-xs mt-1">✓ Clasifica a Fase Final (1° de cada zona + mejor segundo)</p>
                @elseif($dobles && $grupos->count() === 4)
                <p class="text-white/40 text-xs mt-1">✓ Clasifica a Fase Final (1° de cada zona)</p>
                @else
                <p class="text-white/40 text-xs mt-1">✓ Clasifica a Fase Final</p>
                @endif
            </div>
            @endif
        </div>
        @endforeach

        {{-- Dobles con 3 zonas: comparación de segundos por promedios --}}
        @if($dobles && $clasificacion['segundos'])
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden">
            <div class="bg-white/10 px-4 py-2.5 border-b border-white/10">
                <h2 class="text-white font-bold text-base">
                    Mejor segundo
                    @unless($clasificacion['completo'])<span class="text-white/50 text-xs font-normal">(provisorio)</span>@endunless
                </h2>
            </div>
            <div class="px-4 py-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-white/10 text-white/70">
                                <th class="px-2 py-1.5 text-left">Pareja</th>
                                <th class="px-1 py-1.5 text-center">PJ</th>
                                <th class="px-1 py-1.5 text-center">Pts/PJ</th>
                                <th class="px-1 py-1.5 text-center">%Sets</th>
                                <th class="px-1 py-1.5 text-center">%Games</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clasificacion['segundos'] as $s)
                            <tr class="{{ !empty($s['mejor']) ? 'bg-white/10' : '' }} border-t border-white/10">
                                <td class="px-2 py-1.5 font-semibold text-white leading-tight">
                                    {{ $s['jugador']->apellido }}
                                    @if(!empty($s['mejor']))<span class="text-green-300">✓</span>@endif
                                    <span class="block text-white/40 font-normal">{{ $s['nombre_zona'] }}</span>
                                </td>
                                <td class="px-1 py-1.5 text-center text-white/70">{{ $s['pj'] }}</td>
                                <td class="px-1 py-1.5 text-center text-white/70">{{ number_format($s['prom_pts'], 2, ',', '.') }}</td>
                                <td class="px-1 py-1.5 text-center text-white/70">{{ number_format($s['pct_sets'], 1, ',', '.') }}%</td>
                                <td class="px-1 py-1.5 text-center text-white/70">{{ number_format($s['pct_games'], 1, ',', '.') }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-white/40 text-xs mt-1">Puntos por partido jugado, después % de sets y % de games. El W.O. cuenta como 6-0 6-0.</p>
            </div>
        </div>
        @endif

        <!-- Fase Final -->
        @if($faseFinal->isNotEmpty())
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden">
            <div class="bg-white/10 px-4 py-2.5 border-b border-white/10">
                <h2 class="text-white font-bold text-base">🏆 Fase Final</h2>
            </div>
            <div class="px-4 py-3 grid grid-cols-1 gap-3">
                @foreach($faseFinal as $match)
                <div class="border border-white/20 rounded-xl p-3 {{ $match->tipo === 'final' ? 'bg-yellow-400/10 border-yellow-400/40' : '' }}">
                    <p class="text-xs font-bold uppercase tracking-wide mb-2
                               {{ $match->tipo === 'final' ? 'text-yellow-300' : 'text-white/50' }}">
                        {{ $match->label() }}
                    </p>
                    @if($match->jugador1_id && $match->jugador2_id)
                        <div class="space-y-1 mb-2">
                            <div class="flex items-center gap-2">
                                @if($match->ganador_id === $match->jugador1_id)<span class="text-yellow-400 text-xs">🥇</span>@else<span class="w-4 inline-block"></span>@endif
                                <span class="text-sm {{ $match->ganador_id === $match->jugador1_id ? 'font-bold text-white' : 'text-white/70' }}">
                                    {{ $match->jugador1->apellido }}@unless($dobles), {{ $match->jugador1->nombre }}@endunless
                                </span>
                            </div>
                            <div class="text-xs text-white/40 pl-6">vs</div>
                            <div class="flex items-center gap-2">
                                @if($match->ganador_id === $match->jugador2_id)<span class="text-yellow-400 text-xs">🥇</span>@else<span class="w-4 inline-block"></span>@endif
                                <span class="text-sm {{ $match->ganador_id === $match->jugador2_id ? 'font-bold text-white' : 'text-white/70' }}">
                                    {{ $match->jugador2->apellido }}@unless($dobles), {{ $match->jugador2->nombre }}@endunless
                                </span>
                            </div>
                        </div>
                        @if($match->resultado)
                            <p class="text-center text-xs font-mono text-green-300">{{ $match->resultado }}</p>
                        @else
                            <p class="text-center text-xs text-white/30 italic">Pendiente</p>
                        @endif
                    @else
                        <p class="text-white/40 text-xs italic">Se define al terminar las semifinales</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
    </div>
</div>
