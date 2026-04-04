<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Draw - {{ $torneo->nombre }} - Cat. {{ $draw->categoria->nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; background: #fff; }
        .page { padding: 10px 14px; }
        h1  { font-size: 13px; margin-bottom: 1px; }
        h2  { font-size: 9px; color: #555; margin-bottom: 10px; font-weight: normal; }

        .wrap { position: relative; }
        .wrap svg { position: absolute; top: 0; left: 0; overflow: visible; pointer-events: none; }

        .match-box { position: absolute; border: 1px solid #aaa; border-radius: 2px;
                     overflow: hidden; background: #fff; }
        .player { padding: 2px 5px; border-bottom: 1px solid #ddd;
                  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                  font-size: 8.5px; line-height: 1.35; }
        .player.ganador  { font-weight: bold; background: #f0fdf4; }
        .player.perdedor { color: #aaa; }
        .res-row { background: #f7f7f7; padding: 1px 5px; font-size: 7.5px;
                   color: #444; font-weight: bold; text-align: right; border-top: 1px solid #ddd; }

        .hdr { position: absolute; font-size: 7.5px; font-weight: bold;
               text-transform: uppercase; letter-spacing: 0.4px; color: #666;
               text-align: center; }
        .hdr-final { color: #16a34a; }

        @media print {
            @page { size: A4 landscape; margin: 0.7cm; }
            body  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
@php
    // ── Dimensiones ───────────────────────────────────────────────────
    $matchW = 118;   // ancho caja partido
    $matchH = 38;    // alto caja (2 filas de jugador × 19px)
    $connW  = 16;    // ancho del conector entre columnas
    $colStep = $matchW + $connW;   // 134
    $slotH1 = 60;    // alto slot primera ronda
    $hdrH   = 18;    // alto de la fila de cabeceras

    // ── Datos ─────────────────────────────────────────────────────────
    $rounds     = $partidos->sortKeysDesc(SORT_NUMERIC);
    $roundsArr  = $rounds->values()->all();
    $roundKeys  = $rounds->keys()->all();
    $nRounds    = count($roundsArr);
    $nFirst     = count($roundsArr[0]);

    // Separar Final del resto
    $nSemiR     = $nRounds - 1;          // columnas por lado (sin final)
    $semiArr    = array_slice($roundsArr, 0, $nSemiR);
    $semiKeys   = array_slice($roundKeys, 0, $nSemiR);
    $finalMatch = $roundsArr[$nRounds - 1][0] ?? null;

    // ── Mitades: izquierda (arriba del bracket) / derecha (abajo) ────
    $leftHalf = [];
    $rightHalf = [];
    foreach ($semiArr as $ri => $rMatches) {
        $arr  = $rMatches->values()->all();
        $half = intdiv(count($arr), 2);
        $leftHalf[$ri]  = array_slice($arr, 0, $half);
        $rightHalf[$ri] = array_slice($arr, $half);
    }

    $nHalfFirst = $nFirst / 2;
    $totalH     = $nHalfFirst * $slotH1;

    // ── Posiciones X ──────────────────────────────────────────────────
    // Lado izquierdo: ri=0 (primera ronda) al borde izquierdo → ri=nSemiR-1 (semi) al centro
    $leftX  = [];
    for ($ri = 0; $ri < $nSemiR; $ri++) {
        $leftX[$ri] = $ri * $colStep;
    }

    // Final justo al centro
    $finalX = ($nSemiR - 1) * $colStep + $matchW + $connW;

    // Lado derecho: ri=0 (primera ronda) al borde derecho → ri=nSemiR-1 (semi) al centro
    $rightX = [];
    for ($ri = 0; $ri < $nSemiR; $ri++) {
        $rightX[$ri] = $finalX + $matchW + $connW + ($nSemiR - 1 - $ri) * $colStep;
    }

    $totalW = $rightX[0] + $matchW;

    // ── Posición Y de cada match (sin offset de cabecera) ─────────────
    $posY = [];
    for ($ri = 0; $ri < $nSemiR; $ri++) {
        $slotH = $slotH1 * pow(2, $ri);
        $nM    = (int)($nHalfFirst / pow(2, $ri));
        for ($mi = 0; $mi < $nM; $mi++) {
            $posY[$ri][$mi] = (int)($mi * $slotH + ($slotH - $matchH) / 2);
        }
    }

    // Final centrado verticalmente
    $finalTop = (int)(($totalH - $matchH) / 2);

    // ── Líneas SVG ────────────────────────────────────────────────────
    // Helper: emite array de segmentos [x1,y1,x2,y2] (Y sin offset de cabecera)
    $lines = [];

    // Lado izquierdo: conectores van de izquierda (primera) hacia centro (semi)
    for ($ri = 0; $ri < $nSemiR - 1; $ri++) {
        $nPairs = intdiv(count($leftHalf[$ri]), 2);
        for ($p = 0; $p < $nPairs; $p++) {
            $y1    = $posY[$ri][$p * 2]     + $matchH / 2;
            $y2    = $posY[$ri][$p * 2 + 1] + $matchH / 2;
            $yMid  = ($y1 + $y2) / 2;
            $xR    = $leftX[$ri] + $matchW;        // borde derecho columna actual
            $xV    = $xR + $connW / 2;             // vértice
            $xN    = $leftX[$ri + 1];              // borde izq columna siguiente
            $yDest = $posY[$ri + 1][$p] + $matchH / 2;
            $lines[] = [[$xR,$y1,$xV,$y1], [$xR,$y2,$xV,$y2],
                        [$xV,$y1,$xV,$y2], [$xV,$yMid,$xN,$yDest]];
        }
    }

    // Lado derecho: conectores van de derecha (primera) hacia centro (semi)
    for ($ri = 0; $ri < $nSemiR - 1; $ri++) {
        $nPairs = intdiv(count($rightHalf[$ri]), 2);
        for ($p = 0; $p < $nPairs; $p++) {
            $y1    = $posY[$ri][$p * 2]     + $matchH / 2;
            $y2    = $posY[$ri][$p * 2 + 1] + $matchH / 2;
            $yMid  = ($y1 + $y2) / 2;
            $xL    = $rightX[$ri];                 // borde izquierdo col actual (sale por izquierda)
            $xV    = $xL - $connW / 2;             // vértice a la izquierda
            $xN    = $rightX[$ri + 1] + $matchW;   // borde derecho col siguiente (hacia centro)
            $yDest = $posY[$ri + 1][$p] + $matchH / 2;
            $lines[] = [[$xL,$y1,$xV,$y1], [$xL,$y2,$xV,$y2],
                        [$xV,$y1,$xV,$y2], [$xV,$yMid,$xN,$yDest]];
        }
    }

    // Semi izquierdo → Final (línea horizontal)
    $semiY       = $posY[$nSemiR - 1][0] + $matchH / 2;
    $leftSemiR   = $leftX[$nSemiR - 1] + $matchW;
    $rightSemiL  = $rightX[$nSemiR - 1];
    $lines[] = [[$leftSemiR, $semiY, $finalX, $finalTop + $matchH / 2]];
    $lines[] = [[$finalX + $matchW, $finalTop + $matchH / 2, $rightSemiL, $semiY]];

    // ── Nombres de ronda ──────────────────────────────────────────────
    $nombreRonda = function(int $ronda, int $tamano) {
        return match(true) {
            $ronda >= $tamano / 2 => '1ª Ronda',
            $ronda === 8          => '16avos',
            $ronda === 4          => 'Cuartos',
            $ronda === 2          => 'Semifinal',
            $ronda === 1          => 'Final',
            default               => 'Ronda',
        };
    };

    $wrapH = $totalH + $hdrH;
@endphp

<div class="page">
    <h1>{{ $torneo->nombre }}</h1>
    <h2>Categoría {{ $draw->categoria->nombre }} — Draw de {{ $draw->tamano }}</h2>

    <div class="wrap" style="width:{{ $totalW }}px; height:{{ $wrapH }}px">

        {{-- Cabeceras de columnas --}}
        @foreach($semiKeys as $ri => $key)
            <div class="hdr" style="left:{{ $leftX[$ri] }}px; width:{{ $matchW }}px; top:0">
                {{ $nombreRonda($key, (int)$draw->tamano) }}
            </div>
            <div class="hdr" style="left:{{ $rightX[$ri] }}px; width:{{ $matchW }}px; top:0">
                {{ $nombreRonda($key, (int)$draw->tamano) }}
            </div>
        @endforeach
        <div class="hdr hdr-final" style="left:{{ $finalX }}px; width:{{ $matchW }}px; top:0">
            Final
        </div>

        {{-- SVG con todas las líneas conectoras --}}
        <svg width="{{ $totalW }}" height="{{ $wrapH }}">
            @foreach($lines as $grupo)
                @foreach($grupo as $seg)
                    <line x1="{{ $seg[0] }}" y1="{{ $hdrH + $seg[1] }}"
                          x2="{{ $seg[2] }}" y2="{{ $hdrH + $seg[3] }}"
                          stroke="#16a34a" stroke-width="1.2"/>
                @endforeach
            @endforeach
        </svg>

        {{-- Cajas: lado izquierdo --}}
        @foreach($leftHalf as $ri => $rMatches)
            @foreach($rMatches as $mi => $partido)
                <div class="match-box"
                     style="top:{{ $hdrH + $posY[$ri][$mi] }}px;
                            left:{{ $leftX[$ri] }}px;
                            width:{{ $matchW }}px">
                    <div class="player {{ $partido->ganador_id == $partido->jugador1_id ? 'ganador' : ($partido->ganador_id ? 'perdedor' : '') }}"
                         style="max-width:{{ $matchW - 10 }}px">
                        {{ $partido->jugador1 ? $partido->jugador1->apellido.', '.$partido->jugador1->nombre : '' }}
                    </div>
                    <div class="player {{ $partido->ganador_id == $partido->jugador2_id ? 'ganador' : ($partido->ganador_id ? 'perdedor' : '') }}"
                         style="max-width:{{ $matchW - 10 }}px">
                        {{ $partido->jugador2 ? $partido->jugador2->apellido.', '.$partido->jugador2->nombre : '' }}
                    </div>
                    @if($partido->resultado)
                        <div class="res-row">{{ $partido->resultado }}</div>
                    @endif
                </div>
            @endforeach
        @endforeach

        {{-- Cajas: lado derecho --}}
        @foreach($rightHalf as $ri => $rMatches)
            @foreach($rMatches as $mi => $partido)
                <div class="match-box"
                     style="top:{{ $hdrH + $posY[$ri][$mi] }}px;
                            left:{{ $rightX[$ri] }}px;
                            width:{{ $matchW }}px">
                    <div class="player {{ $partido->ganador_id == $partido->jugador1_id ? 'ganador' : ($partido->ganador_id ? 'perdedor' : '') }}"
                         style="max-width:{{ $matchW - 10 }}px">
                        {{ $partido->jugador1 ? $partido->jugador1->apellido.', '.$partido->jugador1->nombre : '' }}
                    </div>
                    <div class="player {{ $partido->ganador_id == $partido->jugador2_id ? 'ganador' : ($partido->ganador_id ? 'perdedor' : '') }}"
                         style="max-width:{{ $matchW - 10 }}px">
                        {{ $partido->jugador2 ? $partido->jugador2->apellido.', '.$partido->jugador2->nombre : '' }}
                    </div>
                    @if($partido->resultado)
                        <div class="res-row">{{ $partido->resultado }}</div>
                    @endif
                </div>
            @endforeach
        @endforeach

        {{-- Caja Final --}}
        @if($finalMatch)
            <div class="match-box"
                 style="top:{{ $hdrH + $finalTop }}px;
                        left:{{ $finalX }}px;
                        width:{{ $matchW }}px;
                        border-color:#16a34a; border-width:1.5px">
                <div class="player {{ $finalMatch->ganador_id == $finalMatch->jugador1_id ? 'ganador' : ($finalMatch->ganador_id ? 'perdedor' : '') }}"
                     style="max-width:{{ $matchW - 10 }}px">
                    {{ $finalMatch->jugador1 ? $finalMatch->jugador1->apellido.', '.$finalMatch->jugador1->nombre : '' }}
                </div>
                <div class="player {{ $finalMatch->ganador_id == $finalMatch->jugador2_id ? 'ganador' : ($finalMatch->ganador_id ? 'perdedor' : '') }}"
                     style="max-width:{{ $matchW - 10 }}px">
                    {{ $finalMatch->jugador2 ? $finalMatch->jugador2->apellido.', '.$finalMatch->jugador2->nombre : '' }}
                </div>
                @if($finalMatch->resultado)
                    <div class="res-row">{{ $finalMatch->resultado }}</div>
                @endif
            </div>
        @endif

    </div>
</div>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
