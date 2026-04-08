<div wire:poll.30s class="flex flex-col"
     style="min-height:100dvh; background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('{{ asset('canchatenis.png') }}') center/cover fixed;">

    {{-- Barra superior --}}
    <div class="flex justify-end px-4 pt-3 flex-shrink-0">
        <button onclick="window.close()"
                class="bg-white text-green-900 font-semibold text-sm px-4 py-1.5 rounded-lg shadow hover:bg-green-50 transition">
            Salir
        </button>
    </div>

    {{-- Header compacto --}}
    <div class="text-center pt-2 pb-2 px-4 flex-shrink-0">
        <div class="text-3xl mb-1">🎾</div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight drop-shadow-lg leading-tight">
            {{ $clubNombre }}
        </h1>
        @if($clubCiudad)
            <p class="text-yellow-200 text-sm">{{ $clubCiudad }}</p>
        @endif
        <p class="text-green-300 text-xs mt-0.5 tracking-wide">Gestión de torneos</p>
    </div>

    {{-- Panel Online --}}
    <div class="max-w-2xl mx-auto w-full px-3 pb-6">
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden shadow-xl">

            {{-- Cabecera del panel --}}
            <div class="bg-white/10 px-4 py-2.5 flex items-center gap-2 border-b border-white/10">
                <span class="text-lg">📺</span>
                <h2 class="text-white font-bold text-base">Panel Online</h2>
            </div>

            {{-- Ranking + Torneos Finalizados --}}
            <div class="px-3 pt-3 pb-2 grid grid-cols-2 gap-2">
                <a href="{{ route('ranking.publico') }}" wire:navigate
                   class="flex items-center gap-2 bg-yellow-400/20 border border-yellow-300/30
                          rounded-xl px-3 py-2.5 hover:bg-yellow-400/30 transition">
                    <span class="text-xl shrink-0">🏆</span>
                    <div class="min-w-0">
                        <p class="text-white font-semibold text-xs leading-tight">Ranking General</p>
                        <p class="text-yellow-200 text-xs leading-tight">Por categoría</p>
                    </div>
                </a>
                <a href="{{ route('torneos.publico') }}" wire:navigate
                   class="flex items-center gap-2 bg-white/15 border border-white/20
                          rounded-xl px-3 py-2.5 hover:bg-white/25 transition">
                    <span class="text-xl shrink-0">🏅</span>
                    <div class="min-w-0">
                        <p class="text-white font-semibold text-xs leading-tight">Torneos finalizados</p>
                        <p class="text-green-200 text-xs leading-tight">Resultados y draws</p>
                    </div>
                </a>
            </div>

            {{-- Torneos activos --}}
            <div class="px-3 pb-3 pt-1 space-y-2">
                @if($torneos->isEmpty())
                    <div class="text-green-200/70 text-xs italic text-center py-3">
                        No hay torneos disponibles por el momento.
                    </div>
                @else
                    @foreach($torneos as $torneo)
                        <div class="border border-red-500 rounded-xl overflow-hidden">
                            <div class="bg-white/10 px-3 py-2 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-white font-bold text-xs truncate">{{ $torneo->nombre }}</p>
                                    @if($torneo->fecha_inicio)
                                        <p class="text-green-300 text-xs">
                                            Fecha de inicio: {{ \Carbon\Carbon::parse($torneo->fecha_inicio)->format('d/m/Y') }}
                                            @if($torneo->fecha_fin) — {{ \Carbon\Carbon::parse($torneo->fecha_fin)->format('d/m/Y') }} @endif
                                        </p>
                                    @endif
                                </div>
                                @php $tieneCampeon = $torneo->draws->contains(fn($d) => $d->partidos->isNotEmpty()); @endphp
                                @if($tieneCampeon)
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-500 text-white shrink-0">Finalizado</span>
                                @elseif($torneo->estado === 'activo')
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-500 text-white shrink-0">Torneo en curso</span>
                                @endif
                            </div>
                            @if(($torneo->tipo ?? 'normal') === 'master')
                                @if($torneo->masters->isEmpty())
                                    <div class="px-3 py-2 text-yellow-300/60 text-xs italic">⭐ Master — Sin categorías configuradas aún.</div>
                                @else
                                    <div class="divide-y divide-white/10">
                                        @foreach($torneo->masters as $master)
                                            <div class="px-3 py-2 flex items-center gap-2">
                                                <p class="text-white text-xs font-semibold flex-1 min-w-0 truncate">
                                                    ⭐ Cat. {{ $master->categoria->nombre }}
                                                    <span class="text-yellow-300/70 font-normal">Master</span>
                                                </p>
                                                <a href="{{ route('live.master', [$torneo->id, $master->id]) }}" wire:navigate
                                                   class="bg-yellow-400/20 hover:bg-yellow-400/40 border border-yellow-300/40 text-yellow-200 text-xs font-bold px-4 py-2 rounded-lg transition shrink-0">
                                                    Ver Master
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @elseif($torneo->draws->isEmpty())
                                <div class="px-3 py-2 text-green-300/60 text-xs italic">Draw no disponible aún.</div>
                            @else
                                <div class="divide-y divide-white/10">
                                    @foreach($torneo->draws as $draw)
                                        <div class="px-3 py-2 flex items-center gap-2">
                                            <p class="text-white text-xs font-semibold flex-1 min-w-0 truncate">Cat. {{ $draw->categoria->nombre }}</p>
                                            <div class="flex gap-1.5 shrink-0">
                                                <a href="{{ route('live.resultados', [$torneo->id, $draw->id]) }}" wire:navigate
                                                   class="bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                                    📋 Resultados
                                                </a>
                                                <a href="{{ route('live.draw', [$torneo->id, $draw->id]) }}" wire:navigate
                                                   class="bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                                    🎯 Draw
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Admin --}}
            <div class="px-3 pt-1 pb-3 border-t border-white/10">
                <div class="text-center">
                    <a href="{{ route('admin.login') }}" wire:navigate
                       class="text-white/40 hover:text-white/70 text-xs transition">
                        🔐 Panel Administrador → Ingreso al panel
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
