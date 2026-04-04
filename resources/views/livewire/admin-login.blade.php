<div class="min-h-screen flex items-center justify-center px-4"
     style="background: linear-gradient(160deg, #166534 0%, #15803d 40%, #16a34a 70%, #22c55e 100%)">

    <div class="w-full max-w-sm">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="text-6xl mb-3">🔐</div>
            <h1 class="text-2xl font-bold text-white">Panel Administrador</h1>
            <p class="text-green-200 text-sm mt-1">Ingresá el código de acceso</p>
        </div>

        {{-- Card --}}
        <div class="bg-white/15 backdrop-blur-sm border border-white/25 rounded-2xl shadow-2xl p-8">

            <form wire:submit="ingresar" class="space-y-5">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Código de acceso</label>
                    <input
                        wire:model="codigo"
                        type="password"
                        autocomplete="off"
                        placeholder="••••••"
                        class="w-full bg-white/20 border border-white/30 text-white placeholder-white/40
                               rounded-xl px-4 py-3 text-center text-xl tracking-widest
                               focus:outline-none focus:ring-2 focus:ring-white/60 focus:border-transparent"
                    >
                    @error('codigo')
                        <p class="text-red-200 text-sm mt-2 text-center font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-white text-green-800 font-bold py-3 rounded-xl hover:bg-green-50
                               transition shadow-lg text-sm uppercase tracking-wider">
                    Ingresar al panel
                </button>
            </form>

        </div>

        {{-- Volver --}}
        <div class="text-center mt-6">
            <a href="{{ route('bienvenida') }}"
               class="text-green-200 hover:text-white text-sm transition">
                ← Volver al inicio
            </a>
        </div>

    </div>
</div>
