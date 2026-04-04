<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        #nav-links { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        #nav-hamburger { display: none; }
        #nav-dropdown { display: none; border-top: 1px solid #16a34a; background: #166534; }
        @media (max-width: 767px) {
            #nav-links { display: none; }
            #nav-hamburger { display: flex; }
        }
        .nav-link {
            display: block; padding: 12px 12px; border-radius: 6px;
            color: white; text-decoration: none; font-size: .9rem;
        }
        .nav-link:hover { background: #15803d; }
        .nav-link.active { background: #16a34a; font-weight: 700; }
    </style>
</head>
<body style="background:#f3f4f6; min-height:100vh; overflow-x:hidden; margin:0">

    <!-- Navbar -->
    <nav style="background:#166534; color:white; box-shadow:0 2px 8px rgba(0,0,0,.3)">
        <div style="max-width:1280px; margin:0 auto; padding:0 16px">
            <div style="display:flex; align-items:center; justify-content:space-between; height:64px">

                <!-- Logo -->
                <a href="{{ route('torneos') }}"
                   style="display:flex; align-items:center; gap:8px; text-decoration:none; color:white; font-size:1.1rem; font-weight:700; flex-shrink:0">
                    🎾 Panel Admin
                </a>

                <!-- Links desktop -->
                <div id="nav-links">
                    <a href="{{ route('jugadores') }}"
                       style="padding:8px 12px; border-radius:6px; color:white; text-decoration:none; font-size:.9rem; {{ request()->routeIs('jugadores') ? 'background:#16a34a;font-weight:700' : '' }}"
                       onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='{{ request()->routeIs('jugadores') ? '#16a34a' : 'transparent' }}'">
                        Jugadores
                    </a>
                    <a href="{{ route('categorias') }}"
                       style="padding:8px 12px; border-radius:6px; color:white; text-decoration:none; font-size:.9rem; {{ request()->routeIs('categorias') ? 'background:#16a34a;font-weight:700' : '' }}"
                       onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='{{ request()->routeIs('categorias') ? '#16a34a' : 'transparent' }}'">
                        Categorías
                    </a>
                    <a href="{{ route('torneos') }}"
                       style="padding:8px 12px; border-radius:6px; color:white; text-decoration:none; font-size:.9rem; {{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? 'background:#16a34a;font-weight:700' : '' }}"
                       onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='{{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? '#16a34a' : 'transparent' }}'">
                        Torneos
                    </a>
                    <a href="{{ route('ranking') }}"
                       style="padding:8px 12px; border-radius:6px; color:white; text-decoration:none; font-size:.9rem; {{ request()->routeIs('ranking') ? 'background:#16a34a;font-weight:700' : '' }}"
                       onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='{{ request()->routeIs('ranking') ? '#16a34a' : 'transparent' }}'">
                        Ranking
                    </a>
                    <a href="{{ route('config') }}"
                       style="padding:8px 12px; border-radius:6px; color:white; text-decoration:none; font-size:.9rem; {{ request()->routeIs('config') ? 'background:#16a34a;font-weight:700' : '' }}"
                       onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='{{ request()->routeIs('config') ? '#16a34a' : 'transparent' }}'">
                        Config
                    </a>
                    <a href="{{ route('whatsapp') }}"
                       style="padding:8px 12px; border-radius:6px; color:white; text-decoration:none; font-size:.9rem; {{ request()->routeIs('whatsapp') ? 'background:#16a34a;font-weight:700' : '' }}"
                       onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='{{ request()->routeIs('whatsapp') ? '#16a34a' : 'transparent' }}'">
                        WhatsApp
                    </a>
                    <div style="width:1px; height:24px; background:#4ade80; margin:0 6px"></div>
                    <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">
                        @csrf
                        <button type="submit"
                                style="padding:8px 12px; border-radius:6px; border:none; background:transparent; color:#86efac; cursor:pointer; font-size:.9rem"
                                onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='transparent'">
                            Salir ↩
                        </button>
                    </form>
                </div>

                <!-- Hamburger -->
                <button id="nav-hamburger" onclick="toggleMenu()" type="button"
                        style="flex-direction:column; justify-content:center; align-items:center; width:44px; height:44px; border-radius:6px; border:none; background:transparent; cursor:pointer; gap:5px">
                    <span id="hb1" style="display:block; width:22px; height:2px; background:white; transition:transform .25s, opacity .25s"></span>
                    <span id="hb2" style="display:block; width:22px; height:2px; background:white; transition:opacity .25s"></span>
                    <span id="hb3" style="display:block; width:22px; height:2px; background:white; transition:transform .25s"></span>
                </button>
            </div>
        </div>

        <!-- Dropdown mobile -->
        <div id="nav-dropdown">
            <div style="padding:8px 16px 12px">
                <a href="{{ route('jugadores') }}" class="nav-link {{ request()->routeIs('jugadores') ? 'active' : '' }}">👤 Jugadores</a>
                <a href="{{ route('categorias') }}" class="nav-link {{ request()->routeIs('categorias') ? 'active' : '' }}">🏷️ Categorías</a>
                <a href="{{ route('torneos') }}" class="nav-link {{ request()->routeIs('torneos') || request()->routeIs('torneo.*') ? 'active' : '' }}">🏆 Torneos</a>
                <a href="{{ route('ranking') }}" class="nav-link {{ request()->routeIs('ranking') ? 'active' : '' }}">📊 Ranking</a>
                <a href="{{ route('config') }}" class="nav-link {{ request()->routeIs('config') ? 'active' : '' }}">⚙️ Config</a>
                <a href="{{ route('whatsapp') }}" class="nav-link {{ request()->routeIs('whatsapp') ? 'active' : '' }}">💬 WhatsApp</a>
                <div style="border-top:1px solid #4ade80; margin:8px 0 4px"></div>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="nav-link" style="width:100%; text-align:left; border:none; cursor:pointer; color:#86efac; background:transparent">
                        ↩ Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Contenido -->
    <main style="max-width:1280px; margin:0 auto; padding:16px">
        {{ $slot }}
    </main>

    <script>
        var menuOpen = false;
        function toggleMenu() {
            menuOpen = !menuOpen;
            document.getElementById('nav-dropdown').style.display = menuOpen ? 'block' : 'none';
            document.getElementById('hb1').style.transform = menuOpen ? 'rotate(45deg) translateY(7px)' : '';
            document.getElementById('hb2').style.opacity = menuOpen ? '0' : '1';
            document.getElementById('hb3').style.transform = menuOpen ? 'rotate(-45deg) translateY(-7px)' : '';
        }
    </script>

    @livewireScripts
</body>
</html>
