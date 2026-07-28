<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Galería de Fotos</h1>
    </div>

    @if(session('galeria_ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('galeria_ok') }}
        </div>
    @endif
    @if(session('galeria_error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('galeria_error') }}
        </div>
    @endif

    <!-- Subir fotos (form propio, fuera del form Livewire) -->
    <div class="bg-white rounded-xl shadow p-6 max-w-2xl mb-6">
        <h2 class="text-lg font-semibold mb-2 text-gray-700 border-b pb-2">📤 Subir fotos</h2>
        <p class="text-sm text-gray-500 mb-4">
            Podés seleccionar varias imágenes a la vez (JPG, PNG o WebP, máx. 4 MB cada una).
            La descripción se aplica a todas las fotos de esta tanda; después podés editarla individualmente.
        </p>

        <form action="{{ route('galeria.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required
                   class="block text-sm text-gray-500
                          file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                          file:text-sm file:font-medium file:bg-green-50 file:text-green-700
                          hover:file:bg-green-100 cursor-pointer">
            <input type="text" name="descripcion" maxlength="300"
                   placeholder="Descripción / título (opcional, aplica a toda la tanda)"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <button type="submit"
                    class="bg-green-700 text-white px-5 py-2 rounded-lg hover:bg-green-800 transition text-sm font-medium">
                Subir fotos
            </button>
        </form>
    </div>

    <!-- Listado -->
    <div class="max-w-4xl">
        @if($fotos->isEmpty())
            <div class="bg-white rounded-xl shadow p-8 text-center text-gray-400">
                Todavía no hay fotos en la galería.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($fotos as $foto)
                    <div wire:key="foto-{{ $foto->id }}" class="bg-white rounded-xl shadow overflow-hidden">
                        <img src="{{ $foto->url }}" alt="{{ $foto->descripcion }}" class="w-full h-40 object-cover">
                        <div class="p-3 space-y-2">
                            <input type="text" maxlength="300"
                                   value="{{ $foto->descripcion }}"
                                   placeholder="Descripción..."
                                   onchange="$wire.actualizarDescripcion({{ $foto->id }}, $event.target.value)"
                                   class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-500">
                            <button wire:click="eliminar({{ $foto->id }})"
                                    wire:confirm="¿Eliminar esta foto?"
                                    type="button"
                                    class="w-full bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold px-3 py-2 rounded-lg transition">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
