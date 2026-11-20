{{--
    Cuadro de llaves de la fase final de un Master / Dobles (solo vista pública).
    Recibe: $faseFinal (Collection de MasterFinal con jugador1, jugador2, ganador) y $dobles (bool).
    Medidas y líneas con estilos en línea: así no depende de clases nuevas de Tailwind (no hay que compilar).
--}}
@php
    $porTipo    = $faseFinal->keyBy('tipo');
    $conCuartos = $faseFinal->contains(fn($m) => $m->esCuartos());

    $rondas = [];
    if ($conCuartos) {
        $rondas[] = ['titulo' => 'Cuartos', 'tipos' => ['cuartos_1', 'cuartos_2', 'cuartos_3', 'cuartos_4']];
    }
    $rondas[] = ['titulo' => 'Semifinales', 'tipos' => ['semifinal_1', 'semifinal_2']];
    $rondas[] = ['titulo' => 'Final', 'tipos' => ['final']];

    // De qué partido viene cada lugar: [tipo => [origen del jugador1, origen del jugador2]]
    $origen = ['final' => ['semifinal_1', 'semifinal_2']];
    if ($conCuartos) {
        foreach (\App\Models\MasterFinal::CUARTOS_A_SEMI as $cuarto => [$semi, $lugar]) {
            $origen[$semi][$lugar === 'jugador1_id' ? 0 : 1] = $cuarto;
        }
    }

    $etiqueta = fn(string $tipo) => match(true) {
        str_starts_with($tipo, 'cuartos_') => 'Cuarto ' . substr($tipo, -1),
        str_starts_with($tipo, 'semifinal_') => 'Semifinal ' . substr($tipo, -1),
        default => 'Final',
    };

    // Jugador de un lugar del partido; si todavía no se cargó pero el partido de origen ya tiene ganador, se lo adelanta.
    // Devuelve [Jugador|null, texto si no se sabe]
    $lado = function (string $tipo, int $i) use ($porTipo, $origen) {
        $m   = $porTipo->get($tipo);
        $j   = $m ? ($i === 0 ? $m->jugador1 : $m->jugador2) : null;
        $src = $origen[$tipo][$i] ?? null;
        if (!$j && $src) {
            $j = $porTipo->get($src)?->ganador;
        }
        $texto = $src
            ? 'Ganador ' . (str_starts_with($src, 'cuartos_') ? 'cuarto ' : 'semifinal ') . substr($src, -1)
            : 'A definir';
        return [$j, $texto];
    };

    $nombre = fn($j) => $dobles ? $j->apellido : $j->apellido . ', ' . $j->nombre;

    $campeon = $porTipo->get('final')?->ganador;

    // Medidas (px)
    $COL   = 150;  // ancho de cada ronda
    $CONN  = 20;   // ancho de las líneas entre rondas
    $CHAMP = 120;  // ancho del casillero de campeón
    $SLOT  = 118;  // alto de cada partido de la primera ronda
    $altoCuadro  = count($rondas[0]['tipos']) * $SLOT;
    $anchoCuadro = count($rondas) * ($COL + $CONN) + $CHAMP;
    $linea = 'rgba(255,255,255,0.35)';
@endphp

<div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden"
     x-data="{ desborda: false }"
     x-init="$nextTick(() => desborda = $refs.cuadro.scrollWidth > $refs.cuadro.clientWidth + 4)">
    <div class="bg-white/10 px-4 py-2.5 border-b border-white/10 flex items-center justify-between gap-2">
        <h2 class="text-white font-bold text-base">🏆 Fase Final</h2>
        <span x-show="desborda" style="display:none" class="text-white/50 text-xs">Deslizá para ver la final →</span>
    </div>

    <div class="overflow-x-auto" x-ref="cuadro">
        <div style="width:{{ $anchoCuadro }}px; padding:12px; box-sizing:content-box; margin:0 auto;">

            {{-- Títulos de las rondas --}}
            <div style="display:flex; margin-bottom:6px;">
                @foreach($rondas as $ronda)
                    <div style="width:{{ $COL }}px; flex-shrink:0;" class="text-center">
                        <span class="text-green-300 text-xs font-semibold uppercase tracking-wide">{{ $ronda['titulo'] }}</span>
                    </div>
                    <div style="width:{{ $CONN }}px; flex-shrink:0;"></div>
                @endforeach
                <div style="width:{{ $CHAMP }}px; flex-shrink:0;" class="text-center">
                    <span class="text-yellow-300 text-xs font-semibold uppercase tracking-wide">Campeón</span>
                </div>
            </div>

            <div style="display:flex; align-items:stretch; height:{{ $altoCuadro }}px;">
                @foreach($rondas as $ri => $ronda)

                    {{-- Columna de la ronda --}}
                    <div style="width:{{ $COL }}px; flex-shrink:0; display:flex; flex-direction:column;">
                        @foreach($ronda['tipos'] as $tipo)
                            @php
                                $m       = $porTipo->get($tipo);
                                $esFinal = $tipo === 'final';
                                $lados   = [$lado($tipo, 0), $lado($tipo, 1)];
                                $ambos   = $lados[0][0] && $lados[1][0];
                            @endphp
                            <div style="flex:1 1 0; min-height:0; display:flex; align-items:center; padding:4px 0;">
                                <div style="width:100%; border-radius:12px; overflow:hidden; border:1px solid {{ $esFinal ? 'rgba(250,204,21,0.5)' : 'rgba(255,255,255,0.2)' }}; background:{{ $esFinal ? 'rgba(250,204,21,0.10)' : 'rgba(0,0,0,0.25)' }};">
                                    <div class="uppercase font-bold tracking-wide {{ $esFinal ? 'text-yellow-300' : 'text-white/50' }}"
                                         style="font-size:10px; padding:3px 8px; background:rgba(255,255,255,0.08);">
                                        {{ $etiqueta($tipo) }}
                                    </div>
                                    @foreach($lados as $k => [$j, $texto])
                                        @php
                                            $gano  = $j && $m?->ganador_id && (int) $m->ganador_id === (int) $j->id;
                                            $perdio = $j && $m?->ganador_id && !$gano;
                                        @endphp
                                        <div class="text-xs leading-tight {{ $gano ? 'font-bold text-white' : ($perdio ? 'text-white/40' : 'text-white/80') }}"
                                             style="padding:5px 8px; {{ $k === 0 ? 'border-bottom:1px solid rgba(255,255,255,0.1);' : '' }} {{ $gano ? 'background:rgba(74,222,128,0.20);' : '' }}">
                                            @if($j)
                                                {{ $nombre($j) }}
                                            @else
                                                <span class="text-white/40 italic">{{ $texto }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    <div class="text-xs text-right" style="padding:2px 8px; min-height:20px; background:rgba(255,255,255,0.05); border-top:1px solid rgba(255,255,255,0.1);">
                                        @if($m?->resultado)
                                            <span class="font-mono text-green-300">{{ $m->resultado }}</span>
                                        @elseif($ambos)
                                            <span class="text-white/30 italic">Pendiente</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Líneas hacia la ronda siguiente (o hacia el campeón) --}}
                    <div style="width:{{ $CONN }}px; flex-shrink:0; display:flex; flex-direction:column;">
                        @if($ri < count($rondas) - 1)
                            @for($c = 0; $c < count($ronda['tipos']) / 2; $c++)
                                <div style="flex:1 1 0; min-height:0; position:relative;">
                                    <div style="position:absolute; top:25%; left:0; width:{{ $CONN / 2 }}px; height:2px; background:{{ $linea }}; transform:translateY(-50%);"></div>
                                    <div style="position:absolute; top:75%; left:0; width:{{ $CONN / 2 }}px; height:2px; background:{{ $linea }}; transform:translateY(-50%);"></div>
                                    <div style="position:absolute; left:{{ $CONN / 2 }}px; top:25%; bottom:25%; width:2px; background:{{ $linea }};"></div>
                                    <div style="position:absolute; top:50%; left:{{ $CONN / 2 }}px; width:{{ $CONN / 2 }}px; height:2px; background:{{ $linea }}; transform:translateY(-50%);"></div>
                                </div>
                            @endfor
                        @else
                            <div style="flex:1 1 0; position:relative;">
                                <div style="position:absolute; top:50%; left:0; right:0; height:2px; background:rgba(250,204,21,0.5); transform:translateY(-50%);"></div>
                            </div>
                        @endif
                    </div>

                @endforeach

                {{-- Campeón --}}
                <div style="width:{{ $CHAMP }}px; flex-shrink:0; display:flex; align-items:center;">
                    <div class="text-center" style="width:100%; border-radius:12px; padding:10px 8px; border:1px solid rgba(250,204,21,0.6); background:{{ $campeon ? 'rgba(250,204,21,0.25)' : 'rgba(250,204,21,0.08)' }};">
                        <div style="font-size:20px; line-height:1;">🏆</div>
                        @if($campeon)
                            <div class="text-sm font-bold text-white leading-tight" style="margin-top:4px;">{{ $nombre($campeon) }}</div>
                        @else
                            <div class="text-xs text-white/40 italic" style="margin-top:4px;">A definir</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
