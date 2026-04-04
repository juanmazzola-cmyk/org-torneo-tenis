<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Enviar mensaje por WhatsApp</h1>
    </div>

    @if(!$generado)

        {{-- Mensaje --}}
        <div class="bg-white rounded-xl shadow p-5 mb-5">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mensaje</label>
            <textarea wire:model="mensaje"
                      rows="4"
                      placeholder="Escribí el mensaje que querés enviar..."
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 resize-none"></textarea>
            @error('mensaje')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Selección de jugadores --}}
        <div class="bg-white rounded-xl shadow p-5 mb-5">
            <div class="flex items-center justify-between mb-3">
                <label class="text-sm font-semibold text-gray-700">
                    Jugadores
                    <span class="text-gray-400 font-normal">({{ count($seleccionados) }} seleccionados)</span>
                </label>
                <div class="flex gap-2">
                    <button wire:click="seleccionarTodos"
                            class="text-xs text-green-600 hover:underline">Seleccionar todos</button>
                    <span class="text-gray-300">|</span>
                    <button wire:click="deseleccionarTodos"
                            class="text-xs text-red-500 hover:underline">Deseleccionar</button>
                </div>
            </div>

            @error('seleccionados')
                <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-80 overflow-y-auto pr-1">
                @foreach($jugadores as $jugador)
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer transition
                                  {{ in_array((string)$jugador->id, $seleccionados)
                                     ? 'border-green-400 bg-green-50'
                                     : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="checkbox"
                               wire:model="seleccionados"
                               value="{{ $jugador->id }}"
                               class="accent-green-600">
                        <span class="text-sm text-gray-800 flex-1 min-w-0 truncate">
                            {{ $jugador->apellido }}, {{ $jugador->nombre }}
                        </span>
                        @if($jugador->telefono)
                            <span class="text-xs text-gray-400 shrink-0">{{ $jugador->telefono }}</span>
                        @else
                            <span class="text-xs text-red-400 shrink-0">sin tel.</span>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button wire:click="generar"
                    class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-semibold flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.134.558 4.133 1.532 5.87L0 24l6.337-1.51A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.844 0-3.568-.49-5.055-1.345L3 21.91l1.27-3.83A9.945 9.945 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                </svg>
                Generar links de WhatsApp
            </button>
        </div>

    @else

        {{-- Links generados --}}
        <div class="bg-white rounded-xl shadow p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-semibold text-gray-800">Links generados ({{ count($links) }} jugadores)</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Hacé clic en cada botón para abrir WhatsApp con el mensaje listo.</p>
                </div>
                <button wire:click="limpiar"
                        class="text-sm text-gray-500 hover:text-gray-700 underline">
                    ← Nuevo mensaje
                </button>
            </div>

            {{-- Vista previa del mensaje --}}
            <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 mb-5 text-sm text-gray-700 whitespace-pre-wrap">
                {{ $mensaje }}
            </div>

            @if(empty($links))
                <p class="text-red-500 text-sm">Ninguno de los jugadores seleccionados tiene teléfono cargado.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($links as $link)
                        <a href="{{ $link['url'] }}" target="_blank"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg border border-green-300 bg-green-50 hover:bg-green-100 transition group">
                            <svg class="w-6 h-6 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.134.558 4.133 1.532 5.87L0 24l6.337-1.51A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.844 0-3.568-.49-5.055-1.345L3 21.91l1.27-3.83A9.945 9.945 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-800 group-hover:text-green-800">
                                {{ $link['nombre'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    @endif
</div>
