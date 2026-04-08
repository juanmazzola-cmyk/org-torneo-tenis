<div class="min-h-screen flex flex-col"
     style="background: linear-gradient(160deg, #166534 0%, #15803d 40%, #16a34a 70%, #22c55e 100%)">

    {{-- Header --}}
    <div class="sticky top-0 z-10 bg-green-900/90 backdrop-blur border-b border-green-700/50 shadow-lg">
        <div class="max-w-2xl mx-auto px-4 md:px-8 py-3 flex items-center gap-3">
            <a href="{{ route('bienvenida') }}" wire:navigate class="text-green-400 hover:text-white transition text-sm shrink-0">← Volver</a>
            <h1 class="text-white font-bold text-lg">🏅 Torneos finalizados</h1>
        </div>
    </div>

    {{-- Contenido --}}
    <div class="flex-1 max-w-2xl mx-auto w-full px-4 md:px-8 py-8 space-y-5">

        @if($torneos->isEmpty())
            <div class="bg-white/10 rounded-2xl p-10 text-center text-green-200">
                Aún no hay torneos finalizados.
            </div>
        @else
            @foreach($torneos as $torneo)
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden shadow-xl">

                    <div class="bg-white/10 px-5 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-white font-bold">{{ $torneo->nombre }}</p>
                            @if($torneo->fecha_inicio)
                                <p class="text-green-300 text-xs mt-0.5">
                                    {{ \Carbon\Carbon::parse($torneo->fecha_inicio)->format('d/m/Y') }}
                                    @if($torneo->fecha_fin) — {{ \Carbon\Carbon::parse($torneo->fecha_fin)->format('d/m/Y') }} @endif
                                </p>
                            @endif
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-500 text-white shrink-0">
                            Finalizado
                        </span>
                    </div>

                    <div class="divide-y divide-white/10">
                        @foreach($torneo->masters->where('estado', 'finalizado') as $master)
                            <div class="px-5 py-3 flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-semibold text-sm">
                                        ⭐ Categoría {{ $master->categoria->nombre }}
                                        <span class="text-yellow-300 text-xs font-normal ml-1">Master</span>
                                    </p>
                                </div>
                                <a href="{{ route('live.master', [$torneo->id, $master->id]) }}" wire:navigate
                                   class="bg-yellow-400/20 hover:bg-yellow-400/40 border border-yellow-300/40
                                          text-yellow-200 text-xs font-bold px-4 py-2 rounded-lg transition shrink-0">
                                    Ver Master
                                </a>
                            </div>
                        @endforeach
                        @foreach($torneo->draws as $draw)
                            <div class="px-5 py-3">
                                <p class="text-white font-semibold text-sm mb-2">
                                    Categoría {{ $draw->categoria->nombre }}
                                    <span class="text-yellow-300 text-xs font-normal ml-1">Draw de {{ $draw->tamano }}</span>
                                </p>
                                <div class="flex gap-2">
                                    <a href="{{ route('live.resultados', [$torneo->id, $draw->id]) }}" wire:navigate
                                       class="flex-1 text-center bg-white/15 hover:bg-white/25 border border-white/20
                                              text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                                        📋 Resultados
                                    </a>
                                    <a href="{{ route('live.draw', [$torneo->id, $draw->id]) }}" wire:navigate
                                       class="flex-1 text-center bg-green-500 hover:bg-green-400
                                              text-white text-xs font-bold px-3 py-2 rounded-lg transition shadow">
                                        🎯 Draw
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            @endforeach
        @endif
    </div>

</div>
