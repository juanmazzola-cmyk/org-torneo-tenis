<div class="min-h-screen flex flex-col"
     style="background: linear-gradient(160deg, #166534 0%, #15803d 40%, #16a34a 70%, #22c55e 100%)">

    {{-- Header --}}
    <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
        <div class="max-w-5xl mx-auto px-4 md:px-8 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('bienvenida') }}" wire:navigate class="text-green-400 hover:text-white transition text-sm">← Volver</a>
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

    {{-- Contenido --}}
    <div class="flex-1 max-w-5xl mx-auto w-full px-4 md:px-8 py-8 space-y-10">

        @if(empty($categoriasData))
            <div class="bg-white/10 rounded-2xl p-10 text-center text-green-200">
                No hay datos de ranking disponibles aún.
            </div>
        @else
            @foreach($categoriasData as $bloque)
                <div>
                    <h2 class="text-white font-bold text-lg mb-4">
                        <span class="bg-white/20 rounded-full px-4 py-1.5 text-sm">
                            Categoría {{ $bloque['categoria']->nombre }}
                        </span>
                    </h2>

                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-x-auto shadow-xl">
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
