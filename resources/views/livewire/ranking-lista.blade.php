<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ranking</h1>
        <button wire:click="exportar"
                class="flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition text-sm font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar Excel
        </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow p-4 mb-6 flex flex-col sm:flex-row gap-3">
        <select wire:model.live="filtroTorneo"
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">Todos los torneos</option>
            @foreach($torneos as $t)
                <option value="{{ $t->id }}">{{ $t->nombre }}</option>
            @endforeach
        </select>
        <select wire:model.live="filtroCategoria"
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">Todas las categorías</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
            @endforeach
        </select>
    </div>

    @if(empty($categoriasData))
        <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400">
            No hay datos de ranking todavía.
            Los puntos se cargan automáticamente al registrar resultados en los draws.
        </div>
    @else
        @foreach($categoriasData as $bloque)
            <div class="mb-8">
                <h2 class="text-lg font-bold text-gray-700 mb-3">
                    Categoría {{ $bloque['categoria']->nombre }}
                </h2>
                <div class="bg-white rounded-xl shadow overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-8">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jugador</th>
                                @foreach($bloque['torneos'] as $torneo)
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase whitespace-nowrap">
                                        {{ $torneo->nombre }}
                                    </th>
                                @endforeach
                                <th class="px-4 py-3 text-center text-xs font-medium text-yellow-600 uppercase">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Torneos jugados">T.J.</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Mejor puntaje en un torneo">Mejor</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Sets ganados menos perdidos">Sets +/-</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase" title="Games ganados menos perdidos">Games +/-</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($bloque['jugadores'] as $pos => $fila)
                                <tr class="hover:bg-gray-50 {{ $pos < 8 ? 'bg-green-50/30' : '' }}">
                                    <td class="px-4 py-3 text-center font-bold
                                        {{ $pos === 0 ? 'text-yellow-500' : ($pos === 1 ? 'text-gray-400' : ($pos === 2 ? 'text-orange-400' : 'text-gray-400')) }}">
                                        {{ $pos + 1 }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">
                                        {{ $fila['jugador']->apellido }}, {{ $fila['jugador']->nombre }}
                                        @if($bloque['hay_torneo_finalizado'] && $pos < 8)
                                            <span class="ml-1 text-xs text-green-600 font-normal">★ Master</span>
                                        @endif
                                    </td>
                                    @foreach($bloque['torneos'] as $torneo)
                                        <td class="px-4 py-3 text-center text-gray-600">
                                            {{ $fila['puntos'][$torneo->id] > 0 ? $fila['puntos'][$torneo->id] : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-center font-bold text-yellow-700 text-base">
                                        {{ $fila['total'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-500">{{ $fila['torneos_jugados'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-500">{{ $fila['mejor_torneo'] }}</td>
                                    <td class="px-4 py-3 text-center {{ $fila['sets_diff'] >= 0 ? 'text-green-600' : 'text-red-500' }} font-medium">
                                        {{ $fila['sets_diff'] >= 0 ? '+' : '' }}{{ $fila['sets_diff'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center {{ $fila['games_diff'] >= 0 ? 'text-green-600' : 'text-red-500' }} font-medium">
                                        {{ $fila['games_diff'] >= 0 ? '+' : '' }}{{ $fila['games_diff'] }}
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
