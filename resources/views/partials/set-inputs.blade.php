{{-- Variables esperadas: $j1nombre, $j2nombre --}}
<div>
    {{-- Nombres encima de los inputs --}}
    <div style="display:flex; align-items:flex-end; gap:8px; margin-bottom:4px;">
        <span style="width:40px; flex-shrink:0;"></span>
        <span style="width:44px; text-align:center; font-size:11px; font-weight:600; color:#374151; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">{{ $j1nombre }}</span>
        <span style="width:24px;"></span>
        <span style="width:44px; text-align:center; font-size:11px; font-weight:600; color:#374151; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">{{ $j2nombre }}</span>
    </div>

    {{-- Filas de sets --}}
    @foreach([['s1j1','s1j2','Set 1',false],['s2j1','s2j2','Set 2',false],['s3j1','s3j2','Set 3',true]] as [$a,$b,$lbl,$opc])
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
        <span style="width:40px; flex-shrink:0; font-size:11px; color:#9ca3af;">{{ $lbl }}</span>
        <input type="text" wire:model="{{ $a }}" inputmode="numeric" maxlength="2"
               style="width:44px; text-align:center; border:1px solid #d1d5db; border-radius:8px; padding:6px 4px; font-size:14px; font-weight:700; font-family:monospace; outline:none; {{ $opc ? 'color:#9ca3af;' : '' }}"
               placeholder="{{ $opc ? '–' : '0' }}">
        <span style="width:24px; text-align:center; font-weight:700; color:#9ca3af; user-select:none;">—</span>
        <input type="text" wire:model="{{ $b }}" inputmode="numeric" maxlength="2"
               style="width:44px; text-align:center; border:1px solid #d1d5db; border-radius:8px; padding:6px 4px; font-size:14px; font-weight:700; font-family:monospace; outline:none; {{ $opc ? 'color:#9ca3af;' : '' }}"
               placeholder="{{ $opc ? '–' : '0' }}">
        @if($opc)<span style="font-size:11px; color:#9ca3af;">opc.</span>@endif
    </div>
    @endforeach
</div>
