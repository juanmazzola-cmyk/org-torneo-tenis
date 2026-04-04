<div>
    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('torneos') }}" class="text-sm text-green-700 hover:underline mb-1 inline-block">← Volver a torneos</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $torneo->nombre }}</h1>
            <div class="text-sm text-gray-500 flex gap-4 mt-1">
                <span>📅 {{ $torneo->fecha_inicio->format('d/m/Y') }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $torneo->estado === 'activo' ? 'bg-green-100 text-green-700' :
                       ($torneo->estado === 'finalizado' ? 'bg-gray-100 text-gray-600' : 'bg-red-100 text-red-600') }}">
                    {{ ucfirst($torneo->estado) }}
                </span>
            </div>
        </div>
    </div>

    @if(session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('ok') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 mb-6 gap-1">
        <button wire:click="$set('tab', 'inscripciones')"
                class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition
                    {{ $tab === 'inscripciones' ? 'bg-white border border-b-white border-gray-200 text-green-700 -mb-px' : 'text-gray-500 hover:text-gray-700' }}">
            📋 Inscripciones ({{ $inscripciones->count() }})
        </button>
        <button wire:click="$set('tab', 'draws')"
                class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition
                    {{ $tab === 'draws' ? 'bg-white border border-b-white border-gray-200 text-green-700 -mb-px' : 'text-gray-500 hover:text-gray-700' }}">
            🏆 Draws ({{ $draws->count() }})
        </button>
    </div>

    {{-- ===================== TAB INSCRIPCIONES ===================== --}}
    @if($tab === 'inscripciones')
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Panel izquierdo: inscripción -->
        <div class="bg-white rounded-xl shadow p-5">

            {{-- Selector de modo --}}
            @if(!$editandoInscripcionId)
            <div class="flex rounded-lg overflow-hidden border border-gray-200 mb-4 text-sm font-medium">
                <button wire:click="$set('modoInscripcion','individual')"
                        class="flex-1 py-2 transition
                            {{ $modoInscripcion === 'individual' ? 'bg-green-700 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                    Individual
                </button>
                <button wire:click="$set('modoInscripcion','masiva')"
                        class="flex-1 py-2 transition
                            {{ $modoInscripcion === 'masiva' ? 'bg-green-700 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                    Masiva ✓
                </button>
            </div>
            @endif

            {{-- MODO INDIVIDUAL --}}
            @if($modoInscripcion === 'individual' || $editandoInscripcionId)
            <h2 class="text-base font-semibold mb-3 text-gray-700">
                {{ $editandoInscripcionId ? 'Editar Inscripción' : 'Inscribir Jugador' }}
            </h2>
            <form wire:submit="guardarInscripcion" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jugador *</label>
                    <select wire:model="jugador_id"
                            {{ $editandoInscripcionId ? 'disabled' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">Seleccionar jugador...</option>
                        @foreach($jugadoresDisponibles as $j)
                            <option value="{{ $j->id }}">{{ $j->apellido }}, {{ $j->nombre }}</option>
                        @endforeach
                    </select>
                    @error('jugador_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                    <select wire:model="categoria_id"
                            {{ $editandoInscripcionId ? 'disabled' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">Seleccionar categoría...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
                    <textarea wire:model="observacion" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                              placeholder="Opcional..."></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition font-medium text-sm">
                        {{ $editandoInscripcionId ? 'Actualizar' : 'Inscribir' }}
                    </button>
                    @if($editandoInscripcionId)
                        <button type="button" wire:click="cancelarInscripcion"
                                class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm">
                            Cancelar
                        </button>
                    @endif
                </div>
            </form>
            @endif

            {{-- MODO MASIVA --}}
            @if($modoInscripcion === 'masiva' && !$editandoInscripcionId)
            <h2 class="text-base font-semibold mb-3 text-gray-700">Inscripción masiva</h2>
            <form wire:submit="inscribirMasivo" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría para todos *</label>
                    <select wire:model.live="categoriaMasiva"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">Seleccionar categoría...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoriaMasiva') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <input type="text" wire:model.live.debounce.200ms="buscarMasiva"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="Buscar jugador...">
                </div>

                @error('seleccionados') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                {{-- Seleccionar todos (solo los disponibles) --}}
                @php
                    $todosIds       = $jugadoresMasiva->pluck('id')->toArray();
                    $disponiblesIds = array_values(array_diff($todosIds, $yaInscriptosEnCategoria));
                @endphp
                <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                    <label class="flex items-center gap-2 cursor-pointer font-medium text-gray-700">
                        <input type="checkbox"
                               class="w-4 h-4 rounded text-green-600"
                               wire:click="toggleTodos({{ count($seleccionados) < count($disponiblesIds) ? 'true' : 'false' }}, {{ json_encode($disponiblesIds) }})"
                               {{ count($disponiblesIds) > 0 && count($seleccionados) === count($disponiblesIds) ? 'checked' : '' }}>
                        Seleccionar todos
                    </label>
                    <span>{{ count($seleccionados) }} seleccionado/s</span>
                </div>

                {{-- Lista de jugadores --}}
                <div class="border border-gray-200 rounded-lg overflow-y-auto max-h-72 divide-y divide-gray-100">
                    @forelse($jugadoresMasiva as $j)
                        @php $yaInscripto = in_array($j->id, $yaInscriptosEnCategoria); @endphp
                        <label wire:key="masiva-{{ $j->id }}-{{ $categoriaMasiva }}"
                               class="flex items-center gap-3 px-3 py-2
                                      {{ $yaInscripto ? 'bg-gray-50 cursor-not-allowed opacity-60' : (in_array($j->id, $seleccionados) ? 'bg-green-50 cursor-pointer' : 'hover:bg-green-50 cursor-pointer') }}">
                            <input type="checkbox"
                                   wire:model.live="seleccionados"
                                   value="{{ $j->id }}"
                                   {{ $yaInscripto ? 'disabled' : '' }}
                                   @checked(in_array($j->id, $seleccionados))
                                   class="w-4 h-4 rounded text-green-600">
                            <span class="text-sm flex-1">
                                <span class="font-medium text-gray-800">{{ $j->apellido }}</span>,
                                {{ $j->nombre }}
                                @if($j->categoria)
                                    <span class="text-xs text-gray-400">({{ $j->categoria->nombre }})</span>
                                @endif
                            </span>
                            @if($yaInscripto)
                                <span class="text-xs text-gray-400 shrink-0">Ya inscripto</span>
                            @endif
                        </label>
                    @empty
                        <div class="px-3 py-4 text-center text-gray-400 text-sm">No se encontraron jugadores</div>
                    @endforelse
                </div>

                <button type="submit"
                        class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition font-medium text-sm">
                    Inscribir {{ count($seleccionados) > 0 ? '(' . count($seleccionados) . ')' : '' }} jugadores
                </button>
            </form>
            @endif
        </div>

        <!-- Listado inscripciones -->
        <div class="lg:col-span-3">
            <div class="flex items-center justify-between mb-3">
                <input type="text" wire:model.live.debounce.300ms="buscarJugador"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 w-64"
                       placeholder="Buscar por nombre...">
                <button wire:click="exportarInscripciones"
                        class="flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar Excel
                </button>
            </div>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jugador</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Observación</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($inscripciones as $insc)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-gray-800">{{ $insc->jugador->apellido }}</span>,
                                    {{ $insc->jugador->nombre }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($insc->jugador->telefono)
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600">{{ $insc->jugador->telefono }}</span>
                                            <a href="{{ $insc->jugador->whatsapp_link }}" target="_blank"
                                               title="WhatsApp" class="text-green-500 hover:text-green-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">
                                        {{ $insc->categoria->nombre }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                                    {{ $insc->observacion ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <button wire:click="editarInscripcion({{ $insc->id }})"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">Editar</button>
                                    <button wire:click="confirmarEliminarInscripcion({{ $insc->id }})"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">Quitar</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    No hay jugadores inscriptos todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ===================== TAB DRAWS ===================== --}}
    @if($tab === 'draws')
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Formulario crear draw -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">Configurar Draw</h2>
            <form wire:submit="crearDraw" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                    <select wire:model="drawCategoriaId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                    @error('drawCategoriaId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamaño del Draw *</label>
                    <select wire:model="drawTamano"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="8">8 jugadores</option>
                        <option value="16">16 jugadores</option>
                        <option value="32">32 jugadores</option>
                        <option value="64">64 jugadores</option>
                    </select>
                </div>
                <p class="text-xs text-gray-500">⚠️ Si ya existe un draw para esa categoría, se recreará.</p>
                <button type="submit"
                        class="w-full bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition font-medium text-sm">
                    Generar Draw
                </button>
            </form>
        </div>

        <!-- Listado de draws -->
        <div class="lg:col-span-3 space-y-4">
            @forelse($draws as $draw)
                <div class="bg-white rounded-xl shadow p-4 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-gray-800 text-lg">Categoría {{ $draw->categoria->nombre }}</span>
                        <span class="ml-3 text-sm text-gray-500">Draw de {{ $draw->tamano }}</span>
                    </div>
                    <a href="{{ route('torneo.draw', [$torneo->id, $draw->id]) }}"
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                        Ver / Cargar Resultados
                    </a>
                </div>
            @empty
                <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400">
                    No hay draws creados. Configurá uno usando el formulario.
                </div>
            @endforelse
        </div>
    </div>
    @endif

    <!-- Modal Confirmar Quitar Inscripción -->
    @if($confirmandoEliminarInscripcion)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">¿Quitar inscripción?</h3>
                <p class="text-gray-600 mb-6">El jugador será eliminado de este torneo en esa categoría.</p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelarInscripcion"
                            class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</button>
                    <button wire:click="eliminarInscripcion"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Quitar</button>
                </div>
            </div>
        </div>
    @endif
</div>
