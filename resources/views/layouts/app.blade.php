<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-green-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-2">
                    <a href="{{ route('torneos') }}" class="flex items-center gap-2 hover:opacity-80 transition">
                        <span class="text-2xl">🎾</span>
                        <span class="text-xl font-bold">Panel Admin</span>
                    </a>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('jugadores') }}"
                       class="px-4 py-2 rounded hover:bg-green-700 transition {{ request()->routeIs('jugadores') ? 'bg-green-600 font-semibold' : '' }}">
                        Jugadores
                    </a>
                    <a href="{{ route('categorias') }}"
                       class="px-4 py-2 rounded hover:bg-green-700 transition {{ request()->routeIs('categorias') ? 'bg-green-600 font-semibold' : '' }}">
                        Categorías
                    </a>
                    <a href="{{ route('torneos') }}"
                       class="px-4 py-2 rounded hover:bg-green-700 transition {{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? 'bg-green-600 font-semibold' : '' }}">
                        Torneos
                    </a>
                    <a href="{{ route('ranking') }}"
                       class="px-4 py-2 rounded hover:bg-green-700 transition {{ request()->routeIs('ranking') ? 'bg-green-600 font-semibold' : '' }}">
                        Ranking
                    </a>
                    <a href="{{ route('config') }}"
                       class="px-4 py-2 rounded hover:bg-green-700 transition {{ request()->routeIs('config') ? 'bg-green-600 font-semibold' : '' }}">
                        Config
                    </a>
                    <a href="{{ route('whatsapp') }}"
                       class="px-4 py-2 rounded hover:bg-green-700 transition {{ request()->routeIs('whatsapp') ? 'bg-green-600 font-semibold' : '' }}">
                        WhatsApp
                    </a>

                    {{-- Separador --}}
                    <div class="w-px h-6 bg-green-600 mx-2"></div>

                    {{-- Cerrar sesión --}}
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="px-3 py-2 rounded hover:bg-green-700 transition text-green-300 hover:text-white text-sm">
                            Salir ↩
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
