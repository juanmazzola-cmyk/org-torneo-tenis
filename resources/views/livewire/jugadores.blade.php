<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Jugadores</h1>
        <div class="flex gap-2">
            @if(!$byeExiste)
            <button wire:click="crearBye"
                    class="flex items-center gap-2 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition text-sm font-medium">
                + Jugador Bye
            </button>
            @endif
            <button wire:click="$toggle('mostrarImport')"
                    class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                </svg>
                Importar Excel
            </button>
            <button wire:click="exportar"
                    class="flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exportar Excel
            </button>
        </div>
    </div>

    {{-- Panel importar --}}
    @if($mostrarImport)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-5">
        <h3 class="font-semibold text-blue-800 mb-3">Importar jugadores desde Excel</h3>

        @if($mensajeImport)
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-3 text-sm">
                {{ $mensajeImport }}
            </div>
        @endif

        <p class="text-sm text-blue-700 mb-3">
            El archivo debe tener columnas con encabezados. Se aceptan los nombres:
            <strong>Apellido, Nombre, Telefono, Ciudad, Categoria</strong>
            (sin tildes también funciona).
        </p>

        <form wire:submit="importar" class="flex items-end gap-3 flex-wrap">
            <div>
                <label class="block text-sm font-medium text-blue-800 mb-1">Archivo (.xlsx, .xls, .csv)</label>
                <input type="file" wire:model="archivoImport" accept=".xlsx,.xls,.csv"
                       class="block text-sm text-gray-600 file:mr-3 file:py-2 file:px-4
                              file:rounded-lg file:border-0 file:text-sm file:font-medium
                              file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                @error('archivoImport') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    <span wire:loading wire:target="importar">Importando...</span>
                    <span wire:loading.remove wire:target="importar">Importar</span>
                </button>
                <button type="button" wire:click="$set('mostrarImport', false); $set('mensajeImport', '')"
                        class="px-4 py-2 rounded-lg border border-blue-300 text-blue-700 hover:bg-blue-100 text-sm">
                    Cerrar
                </button>
            </div>
        </form>
    </div>
    @endif

    @if(session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('ok') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Formulario -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">
                {{ $editandoId ? 'Editar Jugador' : 'Nuevo Jugador' }}
            </h2>
            <form wire:submit="guardar" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                    <input type="text" wire:model="apellido"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                           placeholder="Apellido">
                    @error('apellido') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" wire:model="nombre"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                           placeholder="Nombre">
                    @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" wire:model="telefono"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                           placeholder="+54911...">
                    @error('telefono') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" wire:model="ciudad"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                           placeholder="Ciudad">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select wire:model="categoria_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">Sin categoría</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit"
                            class="flex-1 bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800 transition font-medium text-sm">
                        {{ $editandoId ? 'Actualizar' : 'Guardar' }}
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
        <div class="lg:col-span-3">
            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow p-4 mb-4 flex flex-col sm:flex-row gap-3">
                <input type="text" wire:model.live.debounce.300ms="buscar"
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                       placeholder="Buscar por apellido, nombre o ciudad...">
                <select wire:model.live="filtroCategoria"
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white rounded-xl shadow overflow-hidden overflow-x-auto">
                <table class="w-full min-w-[600px]">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Apellido y Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ciudad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cat.</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($jugadores as $j)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-gray-800">{{ $j->apellido }}</span>,
                                    {{ $j->nombre }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($j->telefono)
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600">{{ $j->telefono }}</span>
                                            <a href="{{ $j->whatsapp_link }}" target="_blank"
                                               title="Enviar WhatsApp"
                                               class="text-green-500 hover:text-green-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $j->ciudad ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($j->categoria)
                                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">
                                            {{ $j->categoria->nombre }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <button wire:click="editar({{ $j->id }})"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Editar
                                    </button>
                                    <button wire:click="confirmarEliminar({{ $j->id }})"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    No hay jugadores registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t">
                    {{ $jugadores->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminar -->
    @if($confirmandoEliminar)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">¿Eliminar jugador?</h3>
                <p class="text-gray-600 mb-6">Esta acción no se puede deshacer.</p>
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
