{{-- Variables esperadas: $j1nombre, $j2nombre --}}
@php $col = 80; $sep = 28; $lbl = 36; @endphp
<div>
    <div style="display:flex; align-items:flex-end; gap:8px; margin-bottom:4px;">
        <span style="width:{{ $lbl }}px; flex-shrink:0;"></span>
        <span style="width:{{ $col }}px; text-align:center; font-size:12px; font-weight:600; color:#374151;">{{ $j1nombre }}</span>
        <span style="width:{{ $sep }}px;"></span>
        <span style="width:{{ $col }}px; text-align:center; font-size:12px; font-weight:600; color:#374151;">{{ $j2nombre }}</span>
    </div>
    @foreach([['s1j1','s1j2','Set 1',false],['s2j1','s2j2','Set 2',false],['s3j1','s3j2','Set 3',true]] as [$a,$b,$label,$opc])
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
        <span style="width:{{ $lbl }}px; flex-shrink:0; font-size:11px; color:#9ca3af;">{{ $label }}</span>
        <input type="text" wire:model="{{ $a }}" inputmode="numeric" maxlength="2"
               style="width:{{ $col }}px; text-align:center; border:1px solid #d1d5db; border-radius:8px; padding:6px 4px; font-size:15px; font-weight:700; font-family:monospace; outline:none;{{ $opc ? ' color:#9ca3af;' : '' }}"
               placeholder="{{ $opc ? '–' : '0' }}">
        <span style="width:{{ $sep }}px; text-align:center; font-weight:700; color:#9ca3af; user-select:none;">—</span>
        <input type="text" wire:model="{{ $b }}" inputmode="numeric" maxlength="2"
               style="width:{{ $col }}px; text-align:center; border:1px solid #d1d5db; border-radius:8px; padding:6px 4px; font-size:15px; font-weight:700; font-family:monospace; outline:none;{{ $opc ? ' color:#9ca3af;' : '' }}"
               placeholder="{{ $opc ? '–' : '0' }}">
        @if($opc)<span style="font-size:11px; color:#9ca3af;">opc.</span>@endif
    </div>
    @endforeach
</div>
