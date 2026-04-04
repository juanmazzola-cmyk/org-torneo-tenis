<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen" style="overflow-x: hidden">

    <!-- Navbar -->
    <nav class="bg-green-800 text-white shadow-lg"
         x-data="{ open: false, mobile: window.innerWidth < 768 }"
         @resize.window="mobile = window.innerWidth < 768">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between" style="height:64px">

                {{-- Logo --}}
                <a href="{{ route('torneos') }}" class="flex items-center gap-2 hover:opacity-80 transition" style="flex-shrink:0">
                    <span style="font-size:1.5rem">🎾</span>
                    <span style="font-size:1.1rem; font-weight:700">Panel Admin</span>
                </a>

                {{-- Links desktop --}}
                <div x-show="!mobile" class="flex items-center gap-1">
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
                    <div style="width:1px; height:24px; background:#16a34a; margin:0 4px"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="px-3 py-2 rounded hover:bg-green-700 transition text-sm"
                                style="color:#86efac">
                            Salir ↩
                        </button>
                    </form>
                </div>

                {{-- Hamburger mobile --}}
                <button x-show="mobile" @click="open = !open" type="button"
                        style="display:flex; flex-direction:column; justify-content:center; align-items:center; width:40px; height:40px; border-radius:6px; gap:5px; background:transparent; border:none; cursor:pointer;"
                        class="hover:bg-green-700 transition">
                    <span style="display:block; width:20px; height:2px; background:white; transition:all 0.2s"
                          :style="open ? 'transform: rotate(45deg) translateY(7px)' : ''"></span>
                    <span style="display:block; width:20px; height:2px; background:white; transition:all 0.2s"
                          :style="open ? 'opacity:0' : ''"></span>
                    <span style="display:block; width:20px; height:2px; background:white; transition:all 0.2s"
                          :style="open ? 'transform: rotate(-45deg) translateY(-7px)' : ''"></span>
                </button>
            </div>
        </div>

        {{-- Menú mobile dropdown --}}
        <div x-show="mobile && open" x-transition
             style="border-top: 1px solid #16a34a; background:#166534">
            <div style="padding: 8px 16px">
                <a href="{{ route('jugadores') }}"
                   class="flex items-center gap-2 px-3 py-3 rounded text-sm hover:bg-green-700 transition {{ request()->routeIs('jugadores') ? 'bg-green-600 font-semibold' : '' }}">
                    👤 Jugadores
                </a>
                <a href="{{ route('categorias') }}"
                   class="flex items-center gap-2 px-3 py-3 rounded text-sm hover:bg-green-700 transition {{ request()->routeIs('categorias') ? 'bg-green-600 font-semibold' : '' }}">
                    🏷️ Categorías
                </a>
                <a href="{{ route('torneos') }}"
                   class="flex items-center gap-2 px-3 py-3 rounded text-sm hover:bg-green-700 transition {{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? 'bg-green-600 font-semibold' : '' }}">
                    🏆 Torneos
                </a>
                <a href="{{ route('ranking') }}"
                   class="flex items-center gap-2 px-3 py-3 rounded text-sm hover:bg-green-700 transition {{ request()->routeIs('ranking') ? 'bg-green-600 font-semibold' : '' }}">
                    📊 Ranking
                </a>
                <a href="{{ route('config') }}"
                   class="flex items-center gap-2 px-3 py-3 rounded text-sm hover:bg-green-700 transition {{ request()->routeIs('config') ? 'bg-green-600 font-semibold' : '' }}">
                    ⚙️ Config
                </a>
                <a href="{{ route('whatsapp') }}"
                   class="flex items-center gap-2 px-3 py-3 rounded text-sm hover:bg-green-700 transition {{ request()->routeIs('whatsapp') ? 'bg-green-600 font-semibold' : '' }}">
                    💬 WhatsApp
                </a>
                <div style="border-top: 1px solid #16a34a; margin: 8px 0 4px"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-2 w-full px-3 py-3 rounded text-sm hover:bg-green-700 transition text-left"
                            style="color:#86efac">
                        ↩ Salir
                    </button>
                </form>
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
