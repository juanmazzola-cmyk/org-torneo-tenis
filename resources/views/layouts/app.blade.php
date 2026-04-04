<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        .nav-desktop { display: none; }
        .nav-hamburger { display: flex; }
        .nav-mobile-menu { display: none; }
        @media (min-width: 768px) {
            .nav-desktop { display: flex; }
            .nav-hamburger { display: none; }
            .nav-mobile-menu { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" style="overflow-x:hidden">

    <nav class="bg-green-800 text-white shadow-lg" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between" style="height:64px">

                {{-- Logo --}}
                <a href="{{ route('torneos') }}" class="flex items-center gap-2 hover:opacity-80 transition" style="flex-shrink:0">
                    <span style="font-size:1.5rem">🎾</span>
                    <span style="font-size:1.1rem; font-weight:700">Panel Admin</span>
                </a>

                {{-- Links desktop --}}
                <div class="nav-desktop items-center gap-1">
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
                    <div style="width:1px; height:24px; background:#16a34a; margin:0 6px"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded hover:bg-green-700 transition text-sm" style="color:#86efac">
                            Salir ↩
                        </button>
                    </form>
                </div>

                {{-- Hamburger --}}
                <button class="nav-hamburger flex-col justify-center items-center hover:bg-green-700 transition"
                        @click="open = !open" type="button"
                        style="width:40px; height:40px; border-radius:6px; border:none; cursor:pointer; background:transparent; gap:5px">
                    <span style="display:block; width:20px; height:2px; background:white; transition:transform .2s"
                          :style="open ? 'transform:rotate(45deg) translateY(7px)' : ''"></span>
                    <span style="display:block; width:20px; height:2px; background:white; transition:opacity .2s"
                          :style="open ? 'opacity:0' : ''"></span>
                    <span style="display:block; width:20px; height:2px; background:white; transition:transform .2s"
                          :style="open ? 'transform:rotate(-45deg) translateY(-7px)' : ''"></span>
                </button>
            </div>
        </div>

        {{-- Dropdown mobile --}}
        <div class="nav-mobile-menu" :style="open ? 'display:block' : 'display:none'"
             style="border-top:1px solid #16a34a; background:#166534">
            <div style="padding:8px 16px 12px">
                <a href="{{ route('jugadores') }}"
                   class="flex items-center gap-2 px-3 rounded hover:bg-green-700 transition {{ request()->routeIs('jugadores') ? 'bg-green-600 font-semibold' : '' }}"
                   style="padding-top:12px; padding-bottom:12px; font-size:.9rem">
                    👤 Jugadores
                </a>
                <a href="{{ route('categorias') }}"
                   class="flex items-center gap-2 px-3 rounded hover:bg-green-700 transition {{ request()->routeIs('categorias') ? 'bg-green-600 font-semibold' : '' }}"
                   style="padding-top:12px; padding-bottom:12px; font-size:.9rem">
                    🏷️ Categorías
                </a>
                <a href="{{ route('torneos') }}"
                   class="flex items-center gap-2 px-3 rounded hover:bg-green-700 transition {{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? 'bg-green-600 font-semibold' : '' }}"
                   style="padding-top:12px; padding-bottom:12px; font-size:.9rem">
                    🏆 Torneos
                </a>
                <a href="{{ route('ranking') }}"
                   class="flex items-center gap-2 px-3 rounded hover:bg-green-700 transition {{ request()->routeIs('ranking') ? 'bg-green-600 font-semibold' : '' }}"
                   style="padding-top:12px; padding-bottom:12px; font-size:.9rem">
                    📊 Ranking
                </a>
                <a href="{{ route('config') }}"
                   class="flex items-center gap-2 px-3 rounded hover:bg-green-700 transition {{ request()->routeIs('config') ? 'bg-green-600 font-semibold' : '' }}"
                   style="padding-top:12px; padding-bottom:12px; font-size:.9rem">
                    ⚙️ Config
                </a>
                <a href="{{ route('whatsapp') }}"
                   class="flex items-center gap-2 px-3 rounded hover:bg-green-700 transition {{ request()->routeIs('whatsapp') ? 'bg-green-600 font-semibold' : '' }}"
                   style="padding-top:12px; padding-bottom:12px; font-size:.9rem">
                    💬 WhatsApp
                </a>
                <div style="border-top:1px solid #16a34a; margin:8px 0 4px"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 w-full px-3 rounded hover:bg-green-700 transition text-left"
                            style="padding-top:12px; padding-bottom:12px; font-size:.9rem; color:#86efac; background:transparent; border:none; cursor:pointer">
                        ↩ Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Barra de navegación secundaria (mobile): página actual + volver -->
    <div style="background:#f0fdf4; border-bottom:1px solid #bbf7d0; padding:8px 16px; display:flex; align-items:center; gap:8px">
        <a href="{{ route('torneos') }}"
           style="font-size:.8rem; color:#15803d; text-decoration:none; font-weight:600">
            ← Menú principal
        </a>
        <span style="color:#86efac; font-size:.8rem">|</span>
        <span style="font-size:.8rem; color:#6b7280">
            @if(request()->routeIs('jugadores')) Jugadores
            @elseif(request()->routeIs('categorias')) Categorías
            @elseif(request()->routeIs('torneos')) Torneos
            @elseif(request()->routeIs('torneo.detalle')) Detalle de torneo
            @elseif(request()->routeIs('torneo.draw')) Draw
            @elseif(request()->routeIs('ranking')) Ranking
            @elseif(request()->routeIs('config')) Configuración
            @elseif(request()->routeIs('whatsapp')) WhatsApp
            @else Panel Admin
            @endif
        </span>
    </div>

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
