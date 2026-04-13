<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Configuración</h1>
    </div>

    @if(session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('ok') }}
        </div>
    @endif

    <form wire:submit="guardar" class="space-y-6 max-w-2xl">

        <!-- Datos del Club -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Datos del Club / Organización</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Club</label>
                    <input type="text" wire:model="club_nombre"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="Ej: Club de Tenis XYZ">
                    @error('club_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" wire:model="club_ciudad"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="Ciudad">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" wire:model="club_telefono"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="+54911...">
                </div>
            </div>
        </div>

        <!-- Puntos por ronda -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Puntos por Ronda (Ranking)</h2>
            <p class="text-sm text-gray-500 mb-4">
                Puntos que se otorgan automáticamente al perdedor según la ronda en que fue eliminado.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">🏆 Campeón</label>
                    <input type="number" wire:model="puntos_campeon" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    @error('puntos_campeon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">🥈 Subcampeón</label>
                    <input type="number" wire:model="puntos_subcampeon" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    @error('puntos_subcampeon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semifinal</label>
                    <input type="number" wire:model="puntos_semifinal" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cuartos de Final</label>
                    <input type="number" wire:model="puntos_cuartos" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Octavos de Final</label>
                    <input type="number" wire:model="puntos_octavos" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">16avos de Final</label>
                    <input type="number" wire:model="puntos_16avos" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">32avos de Final</label>
                    <input type="number" wire:model="puntos_32avos" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

        <!-- Acceso admin -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">🔐 Acceso al Panel Admin</h2>
            <p class="text-sm text-gray-500 mb-4">
                Código que se solicita para ingresar al panel de administración desde la pantalla pública.
            </p>
            <div class="max-w-xs">
                <label class="block text-sm font-medium text-gray-700 mb-1">Código de acceso</label>
                <input type="text" wire:model="admin_code"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono tracking-widest
                              focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Mínimo 4 caracteres" maxlength="20" autocomplete="off">
                @error('admin_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-400 mt-1">Puede contener letras y números. Distingue mayúsculas.</p>
            </div>
        </div>

        <!-- Información pública -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-2 text-gray-700 border-b pb-2">ℹ️ Texto de Información Pública</h2>
            <p class="text-sm text-gray-500 mb-4">
                Si completás este campo, aparece una solapa "Información" en el panel público.
                Podés escribir avisos, horarios, novedades, etc. Si lo dejás vacío, la solapa no se muestra.
            </p>
            <textarea wire:model="panel_info" rows="6"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 resize-y"
                      placeholder="Ej: Próxima fecha: sábado 20/4 desde las 9hs. Cancha 1 y 2. ¡Los esperamos!"></textarea>
            @error('panel_info') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
                class="bg-green-700 text-white px-8 py-3 rounded-lg hover:bg-green-800 transition font-medium">
            Guardar Configuración
        </button>
    </form>
</div>
