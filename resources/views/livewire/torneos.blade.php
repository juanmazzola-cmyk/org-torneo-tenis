<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Torneos</h1>
    </div>

    @if(session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('ok') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Formulario -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">
                {{ $editandoId ? 'Editar Torneo' : 'Nuevo Torneo' }}
            </h2>
            <form wire:submit="guardar" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" wire:model="nombre"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                           placeholder="Nombre del torneo">
                    @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio *</label>
                    <input type="date" wire:model="fecha_inicio"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    @error('fecha_inicio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de fin</label>
                    <input type="date" wire:model="fecha_fin"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    @error('fecha_fin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select wire:model="tipo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="normal">Normal (Draw/Bracket)</option>
                        <option value="master">Master (Round Robin)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select wire:model="estado"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="activo">Activo</option>
                        <option value="finalizado">Finalizado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit"
                            class="flex-1 bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition font-medium text-sm">
                        {{ $editandoId ? 'Actualizar' : 'Crear Torneo' }}
                    </button>
                    @if($editandoId)
                        <button type="button" wire:click="cancelar"
                                class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm">
                            Cancelar
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Listado -->
        <div class="lg:col-span-3 space-y-4">
            @forelse($torneos as $torneo)
                <div class="bg-white rounded-xl shadow p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="text-lg font-bold text-gray-800">{{ $torneo->nombre }}</h3>
                            @if(($torneo->tipo ?? 'normal') === 'master')
                            <span class="text-xs px-2 py-1 rounded-full font-medium bg-yellow-100 text-yellow-700">
                                ⭐ Master
                            </span>
                            @endif
                            <span class="text-xs px-2 py-1 rounded-full font-medium
                                {{ $torneo->estado === 'activo' ? 'bg-green-100 text-green-700' :
                                   ($torneo->estado === 'finalizado' ? 'bg-gray-100 text-gray-600' : 'bg-red-100 text-red-600') }}">
                                {{ ucfirst($torneo->estado) }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500 flex gap-4">
                            <span>📅 Inicio: {{ $torneo->fecha_inicio->format('d/m/Y') }}</span>
                            @if($torneo->fecha_fin)
                                <span>📅 Fin: {{ $torneo->fecha_fin->format('d/m/Y') }}</span>
                            @endif
                            <span>👤 {{ $torneo->inscripciones_count }} inscriptos</span>
                        </div>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('torneo.detalle', $torneo->id) }}"
                           class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                            Ver Torneo
                        </a>
                        <button wire:click="editar({{ $torneo->id }})"
                                class="bg-blue-50 text-blue-600 border border-blue-200 px-3 py-2 rounded-lg hover:bg-blue-100 transition text-sm font-medium">
                            Editar
                        </button>
                        <button wire:click="confirmarEliminar({{ $torneo->id }})"
                                class="bg-red-50 text-red-600 border border-red-200 px-3 py-2 rounded-lg hover:bg-red-100 transition text-sm font-medium">
                            Eliminar
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400">
                    No hay torneos registrados. ¡Creá el primero!
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal Confirmar Eliminar -->
    @if($confirmandoEliminar)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">¿Eliminar torneo?</h3>
                <p class="text-gray-600 mb-6">Se eliminarán también todas las inscripciones y draws del torneo.</p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelar"
                            class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</button>
                    <button wire:click="eliminar"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
