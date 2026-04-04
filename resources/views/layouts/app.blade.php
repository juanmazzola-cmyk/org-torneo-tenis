<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen overflow-x-hidden">

    <!-- Navbar -->
    <nav class="bg-green-800 text-white shadow-lg" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('torneos') }}" class="flex items-center gap-2 hover:opacity-80 transition shrink-0">
                    <span class="text-2xl">🎾</span>
                    <span class="text-xl font-bold">Panel Admin</span>
                </a>

                {{-- Links desktop --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('jugadores') }}"
                       class="px-3 py-2 rounded hover:bg-green-700 transition text-sm {{ request()->routeIs('jugadores') ? 'bg-green-600 font-semibold' : '' }}">
                        Jugadores
                    </a>
                    <a href="{{ route('categorias') }}"
                       class="px-3 py-2 rounded hover:bg-green-700 transition text-sm {{ request()->routeIs('categorias') ? 'bg-green-600 font-semibold' : '' }}">
                        Categorías
                    </a>
                    <a href="{{ route('torneos') }}"
                       class="px-3 py-2 rounded hover:bg-green-700 transition text-sm {{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? 'bg-green-600 font-semibold' : '' }}">
                        Torneos
                    </a>
                    <a href="{{ route('ranking') }}"
                       class="px-3 py-2 rounded hover:bg-green-700 transition text-sm {{ request()->routeIs('ranking') ? 'bg-green-600 font-semibold' : '' }}">
                        Ranking
                    </a>
                    <a href="{{ route('config') }}"
                       class="px-3 py-2 rounded hover:bg-green-700 transition text-sm {{ request()->routeIs('config') ? 'bg-green-600 font-semibold' : '' }}">
                        Config
                    </a>
                    <a href="{{ route('whatsapp') }}"
                       class="px-3 py-2 rounded hover:bg-green-700 transition text-sm {{ request()->routeIs('whatsapp') ? 'bg-green-600 font-semibold' : '' }}">
                        WhatsApp
                    </a>
                    <div class="w-px h-6 bg-green-600 mx-1"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="px-3 py-2 rounded hover:bg-green-700 transition text-green-300 hover:text-white text-sm">
                            Salir ↩
                        </button>
                    </form>
                </div>

                {{-- Hamburger mobile --}}
                <button @click="open = !open" type="button"
                        class="md:hidden flex flex-col justify-center items-center w-10 h-10 rounded hover:bg-green-700 transition gap-1.5">
                    <span class="block w-5 h-0.5 bg-white transition-all" :class="open ? 'rotate-45 translate-y-2' : ''"></span>
                    <span class="block w-5 h-0.5 bg-white transition-all" :class="open ? 'opacity-0' : ''"></span>
                    <span class="block w-5 h-0.5 bg-white transition-all" :class="open ? '-rotate-45 -translate-y-2' : ''"></span>
                </button>
            </div>
        </div>

        {{-- Menú mobile dropdown --}}
        <div x-show="open" x-transition class="md:hidden border-t border-green-700 bg-green-800">
            <div class="px-4 py-2 space-y-1">
                <a href="{{ route('jugadores') }}"
                   class="block px-3 py-2.5 rounded text-sm {{ request()->routeIs('jugadores') ? 'bg-green-600 font-semibold' : 'hover:bg-green-700' }}">
                    👤 Jugadores
                </a>
                <a href="{{ route('categorias') }}"
                   class="block px-3 py-2.5 rounded text-sm {{ request()->routeIs('categorias') ? 'bg-green-600 font-semibold' : 'hover:bg-green-700' }}">
                    🏷️ Categorías
                </a>
                <a href="{{ route('torneos') }}"
                   class="block px-3 py-2.5 rounded text-sm {{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? 'bg-green-600 font-semibold' : 'hover:bg-green-700' }}">
                    🏆 Torneos
                </a>
                <a href="{{ route('ranking') }}"
                   class="block px-3 py-2.5 rounded text-sm {{ request()->routeIs('ranking') ? 'bg-green-600 font-semibold' : 'hover:bg-green-700' }}">
                    📊 Ranking
                </a>
                <a href="{{ route('config') }}"
                   class="block px-3 py-2.5 rounded text-sm {{ request()->routeIs('config') ? 'bg-green-600 font-semibold' : 'hover:bg-green-700' }}">
                    ⚙️ Config
                </a>
                <a href="{{ route('whatsapp') }}"
                   class="block px-3 py-2.5 rounded text-sm {{ request()->routeIs('whatsapp') ? 'bg-green-600 font-semibold' : 'hover:bg-green-700' }}">
                    💬 WhatsApp
                </a>
                <div class="border-t border-green-700 pt-2 mt-2">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-3 py-2.5 rounded text-sm text-green-300 hover:bg-green-700">
                            ↩ Salir
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
