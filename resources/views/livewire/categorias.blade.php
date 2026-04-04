<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Categorías</h1>
    </div>

    @if(session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('ok') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Formulario -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">
                {{ $editandoId ? 'Editar Categoría' : 'Nueva Categoría' }}
            </h2>
            <form wire:submit="guardar" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" wire:model="nombre"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="Ej: A, B, C, Senior, Libre...">
                    @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition font-medium">
                        {{ $editandoId ? 'Actualizar' : 'Guardar' }}
                    </button>
                    @if($editandoId)
                        <button type="button" wire:click="cancelar"
                                class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Listado -->
        <div class="md:col-span-2 bg-white rounded-xl shadow">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($categorias as $cat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-lg font-semibold text-gray-800">{{ $cat->nombre }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button wire:click="editar({{ $cat->id }})"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Editar
                                </button>
                                <button wire:click="confirmarEliminar({{ $cat->id }})"
                                        class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-8 text-center text-gray-400">
                                No hay categorías registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Confirmar Eliminar -->
    @if($confirmandoEliminar)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">¿Confirmar eliminación?</h3>
                <p class="text-gray-600 mb-6">Esta acción no se puede deshacer.</p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelar"
                            class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button wire:click="eliminar"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
