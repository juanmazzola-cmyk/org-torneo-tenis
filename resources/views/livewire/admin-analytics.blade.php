<div style="padding:24px; max-width:960px">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px">
        <h2 style="font-size:1.4rem; font-weight:700; color:#166534; margin:0">Analytics</h2>
        <button wire:click="refrescar"
                style="padding:6px 16px; border-radius:6px; border:none; background:#166534; color:white; cursor:pointer; font-size:.85rem">
            <span wire:loading.remove wire:target="refrescar">↻ Refrescar</span>
            <span wire:loading wire:target="refrescar">Cargando…</span>
        </button>
    </div>

    @if ($error)
        <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:16px; color:#dc2626; margin-bottom:20px; font-size:.9rem">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif

    @if (count($debug))
        <div style="background:#1e1e1e; color:#d4d4d4; border-radius:8px; padding:16px; margin-bottom:20px; font-family:monospace; font-size:.8rem; white-space:pre-wrap; overflow-x:auto">
            <strong style="color:#9cdcfe">DEBUG</strong>
{{ json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
        </div>
    @endif

    @if (!$error)

        {{-- Tarjetas de métricas --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:16px; margin-bottom:28px">

            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:20px; text-align:center">
                <div style="font-size:2.2rem; font-weight:800; color:#166534">{{ $activeUsers }}</div>
                <div style="font-size:.82rem; color:#4b5563; margin-top:6px">🟢 Activos ahora</div>
            </div>

            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:20px; text-align:center">
                <div style="font-size:2.2rem; font-weight:800; color:#166534">{{ number_format($visitasHoy) }}</div>
                <div style="font-size:.82rem; color:#4b5563; margin-top:6px">📅 Visitas hoy</div>
            </div>

            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:20px; text-align:center">
                <div style="font-size:2.2rem; font-weight:800; color:#166534">{{ number_format($visitas7dias) }}</div>
                <div style="font-size:.82rem; color:#4b5563; margin-top:6px">📆 Últimos 7 días</div>
            </div>

            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:20px; text-align:center">
                <div style="font-size:2.2rem; font-weight:800; color:#166534">{{ number_format($visitas30dias) }}</div>
                <div style="font-size:.82rem; color:#4b5563; margin-top:6px">🗓️ Últimos 30 días</div>
            </div>

        </div>

        {{-- Top 5 páginas --}}
        @if (count($topPaginas))
            <div style="background:white; border:1px solid #d1fae5; border-radius:10px; overflow:hidden">
                <div style="background:#166534; color:white; padding:12px 20px; font-weight:600; font-size:.95rem">
                    Top 5 páginas más vistas — últimos 30 días
                </div>
                <table style="width:100%; border-collapse:collapse">
                    <thead>
                        <tr style="background:#f0fdf4">
                            <th style="text-align:left; padding:10px 20px; font-size:.82rem; color:#374151; font-weight:600">#</th>
                            <th style="text-align:left; padding:10px 20px; font-size:.82rem; color:#374151; font-weight:600">Página</th>
                            <th style="text-align:right; padding:10px 20px; font-size:.82rem; color:#374151; font-weight:600">Vistas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topPaginas as $i => $item)
                            <tr style="border-top:1px solid #f0fdf4">
                                <td style="padding:10px 20px; color:#9ca3af; font-size:.88rem">{{ $i + 1 }}</td>
                                <td style="padding:10px 20px; font-family:monospace; font-size:.82rem; color:#166534">{{ $item['pagina'] }}</td>
                                <td style="padding:10px 20px; text-align:right; font-weight:700; color:#374151">{{ number_format($item['vistas']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p style="margin-top:14px; font-size:.78rem; color:#9ca3af; text-align:right">
            Datos actualizados cada 10 min · Tiempo real: cada 1 min
        </p>

    @endif
</div>
