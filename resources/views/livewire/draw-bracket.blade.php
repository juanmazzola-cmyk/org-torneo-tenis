<div>
    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('torneo.detalle', $torneo->id) }}"
               class="text-sm text-green-700 hover:underline mb-1 inline-block">← Volver al torneo</a>
            <h1 class="text-2xl font-bold text-gray-800">
                Draw — {{ $torneo->nombre }} — Categoría {{ $draw->categoria->nombre }}
            </h1>
            <p class="text-sm text-gray-500">Draw de {{ $draw->tamano }} jugadores</p>
        </div>
        <a href="{{ route('torneo.draw.imprimir', [$torneo->id, $draw->id]) }}"
           target="_blank"
           class="flex items-center gap-2 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm font-medium">
            🖨️ Imprimir
        </a>
    </div>

    @if(session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('ok') }}
        </div>
    @endif

    @php
        // Rounds ordenadas de primera a final (mayor ronda = primera ronda) — orden numérico
        $rounds        = $partidos->sortKeysDesc(SORT_NUMERIC);
        $roundsArr     = $rounds->values()->all();   // [[match,match,...], ...]
        $roundKeys     = $rounds->keys()->all();     // [4, 2, 1]
        $totalRounds   = count($roundsArr);
        $nFirstRound   = count($roundsArr[0]);       // 4 para draw de 8
        $slotHeight    = 110;                        // px por slot de primera ronda
        $containerH    = $nFirstRound * $slotHeight; // alto total del bracket
        $bracketWAdmin = ($totalRounds * 200) + (($totalRounds - 1) * 40) + 64;

        $nombreRonda = function(int $ronda, int $tamano) {
            return match($ronda) {
                32      => '32avos',
                16      => '16avos',
                8       => 'Octavos',
                4       => 'Cuartos',
                2       => 'Semifinal',
                1       => 'Final',
                default => 'Ronda ' . $ronda
            };
        };
    @endphp

    <div class="bg-white rounded-xl shadow p-4 md:p-6" x-data="{ ajustado: false }" x-init="
        const b = document.getElementById('bracket-admin-inner');
        if (b && window.innerWidth < 768) {
            ajustado = true;
            const escala = (window.innerWidth - 48) / {{ $bracketWAdmin }};
            b.style.transform = 'scale(' + escala + ')';
            b.style.transformOrigin = 'top left';
            b.parentElement.style.height = (b.offsetHeight * escala) + 'px';
        }
    ">
        <div class="mb-3 md:hidden">
            <button @click="
                ajustado = !ajustado;
                const b = document.getElementById('bracket-admin-inner');
                if (ajustado) {
                    const escala = (window.innerWidth - 48) / {{ $bracketWAdmin }};
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
        <div id="bracket-admin-inner" style="transform-origin: top left">
        {{-- Cabecera de rondas --}}
        <div class="flex mb-3" style="gap: 0">
            @foreach($roundsArr as $ri => $rMatches)
                <div style="width: 200px; flex-shrink: 0" class="text-center">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                        {{ $nombreRonda($roundKeys[$ri], (int)$draw->tamano) }}
                    </span>
                </div>
                @if($ri < $totalRounds - 1)
                    <div style="width: 40px; flex-shrink: 0"></div>
                @endif
            @endforeach
        </div>

        {{-- Bracket --}}
        <div class="flex items-stretch" style="height: {{ $containerH }}px; gap: 0">

            @foreach($roundsArr as $ri => $rMatches)
                @php $nMatches = count($rMatches); @endphp

                {{-- Columna de ronda --}}
                <div class="flex flex-col" style="width: 200px; flex-shrink: 0">
                    @foreach($rMatches as $partido)
                        {{-- Cada slot ocupa su parte proporcional --}}
                        <div class="flex-1 flex items-center" style="padding: 4px 0">
                            <div class="w-full rounded-lg overflow-hidden shadow-sm border
                                        {{ $partido->ganador_id ? 'border-green-400' : 'border-gray-200' }}">

                                @php
                                    $byeId      = $byeJugador?->id;
                                    $j1IsBye    = $byeId && $partido->jugador1_id == $byeId;
                                    $j2IsBye    = $byeId && $partido->jugador2_id == $byeId;
                                    $esPrimera  = (int)$roundKeys[$ri] === (int)$primeraRonda;
                                @endphp

                                {{-- Jugador 1 --}}
                                <div class="px-3 py-2 text-sm border-b flex items-center justify-between gap-1
                                    {{ $j1IsBye
                                        ? 'bg-gray-50 text-gray-400 italic'
                                        : ($partido->ganador_id == $partido->jugador1_id
                                            ? 'bg-green-100 font-bold text-green-800'
                                            : ($partido->ganador_id
                                                ? 'bg-gray-50 text-gray-400'
                                                : 'bg-white text-gray-700')) }}">
                                    <span>
                                    @if($partido->jugador1)
                                        {{ $j1IsBye ? 'Bye' : $partido->jugador1->apellido.', '.$partido->jugador1->nombre }}
                                    @else
                                        <span class="text-gray-300 italic text-xs">Vacío</span>
                                    @endif
                                    </span>
                                    @if($esPrimera && (!$partido->ganador_id || $j1IsBye))
                                        <span class="flex items-center gap-1 shrink-0">
                                            @if(!$partido->ganador_id)
                                            <button wire:click="abrirAsignar({{ $partido->id }}, '1')"
                                                    class="text-xs text-blue-500 hover:text-blue-700">✎</button>
                                            @endif
                                            @if($partido->jugador1_id)
                                                <button wire:click="quitarJugador({{ $partido->id }}, '1')"
                                                        class="text-xs text-red-400 hover:text-red-600">✕</button>
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                {{-- Jugador 2 --}}
                                <div class="px-3 py-2 text-sm border-b flex items-center justify-between gap-1
                                    {{ $j2IsBye
                                        ? 'bg-gray-50 text-gray-400 italic'
                                        : ($partido->ganador_id == $partido->jugador2_id
                                            ? 'bg-green-100 font-bold text-green-800'
                                            : ($partido->ganador_id
                                                ? 'bg-gray-50 text-gray-400'
                                                : 'bg-white text-gray-700')) }}">
                                    <span>
                                    @if($partido->jugador2)
                                        {{ $j2IsBye ? 'Bye' : $partido->jugador2->apellido.', '.$partido->jugador2->nombre }}
                                    @else
                                        <span class="text-gray-300 italic text-xs">Vacío</span>
                                    @endif
                                    </span>
                                    @if($esPrimera && (!$partido->ganador_id || $j2IsBye))
                                        <span class="flex items-center gap-1 shrink-0">
                                            @if(!$partido->ganador_id)
                                            <button wire:click="abrirAsignar({{ $partido->id }}, '2')"
                                                    class="text-xs text-blue-500 hover:text-blue-700">✎</button>
                                            @endif
                                            @if($partido->jugador2_id)
                                                <button wire:click="quitarJugador({{ $partido->id }}, '2')"
                                                        class="text-xs text-red-400 hover:text-red-600">✕</button>
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                {{-- Resultado / Botón --}}
                                <div class="bg-gray-50 px-3 py-1 flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-600">{{ $partido->resultado ?? '' }}</span>
                                    @if($partido->jugador1_id && $partido->jugador2_id)
                                        <div class="flex items-center gap-2">
                                            <button wire:click="abrirResultado({{ $partido->id }})"
                                                    class="text-xs font-semibold
                                                        {{ $partido->ganador_id ? 'text-blue-500 hover:text-blue-700' : 'text-green-600 hover:text-green-800' }}">
                                                {{ $partido->ganador_id ? 'Editar' : '+ Resultado' }}
                                            </button>
                                            @if($partido->ganador_id)
                                                <button wire:click="anularResultado({{ $partido->id }})"
                                                        wire:confirm="¿Anular el resultado de este partido? Se quitará el ganador y se limpiarán los resultados derivados."
                                                        class="text-xs font-semibold text-red-400 hover:text-red-600">
                                                    Anular
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Columna conectora (no va después de la última ronda) --}}
                @if($ri < $totalRounds - 1)
                    @php $nConn = $nMatches / 2; @endphp
                    <div class="flex flex-col" style="width: 40px; flex-shrink: 0">
                        @for($c = 0; $c < $nConn; $c++)
                            {{-- Cada ítem conector ocupa 2 slots de la ronda actual --}}
                            <div class="flex-1 relative" style="min-height: 0">

                                {{-- Horizontal superior: desde match de arriba al vértice --}}
                                <div style="
                                    position: absolute;
                                    top: 25%; left: 0;
                                    width: 20px; height: 2px;
                                    background: #16a34a;
                                    transform: translateY(-50%);
                                "></div>

                                {{-- Horizontal inferior: desde match de abajo al vértice --}}
                                <div style="
                                    position: absolute;
                                    top: 75%; left: 0;
                                    width: 20px; height: 2px;
                                    background: #16a34a;
                                    transform: translateY(-50%);
                                "></div>

                                {{-- Barra vertical que une los dos horizontales --}}
                                <div style="
                                    position: absolute;
                                    left: 20px;
                                    top: 25%; bottom: 25%;
                                    width: 2px;
                                    background: #16a34a;
                                    transform: translateY(0);
                                "></div>

                                {{-- Horizontal de salida: desde el vértice al siguiente round --}}
                                <div style="
                                    position: absolute;
                                    top: 50%; left: 20px;
                                    width: 20px; height: 2px;
                                    background: #16a34a;
                                    transform: translateY(-50%);
                                "></div>
                            </div>
                        @endfor
                    </div>
                @endif

            @endforeach
        </div>
        </div>{{-- cierre overflow-x-auto --}}
    </div>{{-- cierre bracket admin --}}

    {{-- Modal asignar jugador (primera ronda / sorteo) --}}
    @if($asignandoPartidoId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Asignar jugador</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Posición {{ $asignandoPosicion }} del sorteo
                </p>
                <form wire:submit="guardarAsignacion" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jugador *</label>
                        <select wire:model="jugadorAsignarId"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Seleccionar...</option>
                            @if($byeJugador)
                                <option value="{{ $byeJugador->id }}">— Bye —</option>
                                <option disabled>──────────────</option>
                            @endif
                            @foreach($inscriptos as $insc)
                                @if(!$byeJugador || $insc->jugador_id != $byeJugador->id)
                                <option value="{{ $insc->jugador_id }}">
                                    {{ $insc->jugador->apellido }}, {{ $insc->jugador->nombre }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                        @error('jugadorAsignarId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" wire:click="cancelarAsignacion"
                                class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-green-700 text-white hover:bg-green-800">Asignar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal cargar resultado --}}
    @if($partidoId)
        @php $partido = App\Models\Partido::with(['jugador1','jugador2'])->find($partidoId); @endphp
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Cargar Resultado</h3>
                <form wire:submit="guardarResultado" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ganador *</label>
                        <div class="space-y-2">
                            @if($partido?->jugador1)
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50
                                             {{ $ganadorId == $partido->jugador1_id ? 'border-green-500 bg-green-50' : 'border-gray-200' }}">
                                    <input type="radio" wire:model="ganadorId"
                                           value="{{ $partido->jugador1_id }}" class="text-green-600">
                                    <span class="font-medium">{{ $partido->jugador1->apellido }}, {{ $partido->jugador1->nombre }}</span>
                                </label>
                            @endif
                            @if($partido?->jugador2)
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50
                                             {{ $ganadorId == $partido->jugador2_id ? 'border-green-500 bg-green-50' : 'border-gray-200' }}">
                                    <input type="radio" wire:model="ganadorId"
                                           value="{{ $partido->jugador2_id }}" class="text-green-600">
                                    <span class="font-medium">{{ $partido->jugador2->apellido }}, {{ $partido->jugador2->nombre }}</span>
                                </label>
                            @endif
                        </div>
                        @error('ganadorId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Resultado (opcional)</label>
                        @include('partials.set-inputs', [
                            'j1nombre' => $partido?->jugador1?->apellido ?? 'J1',
                            'j2nombre' => $partido?->jugador2?->apellido ?? 'J2',
                        ])
                    </div>
                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" wire:click="cancelarResultado"
                                class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-green-700 text-white hover:bg-green-800">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
