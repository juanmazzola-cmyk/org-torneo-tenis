{{-- Variables esperadas: $j1nombre, $j2nombre --}}
<div>
    {{-- Nombres encima de los inputs --}}
    <div class="flex items-end gap-2 mb-1">
        <span class="shrink-0 w-10"></span>
        <span class="w-10 text-center text-xs font-semibold text-gray-700 truncate">{{ $j1nombre }}</span>
        <span class="w-6"></span>
        <span class="w-10 text-center text-xs font-semibold text-gray-700 truncate">{{ $j2nombre }}</span>
    </div>

    {{-- Filas de sets --}}
    @foreach([['s1j1','s1j2','Set 1',false],['s2j1','s2j2','Set 2',false],['s3j1','s3j2','Set 3',true]] as [$a,$b,$lbl,$opc])
    <div class="flex items-center gap-2 mb-1.5">
        <span class="shrink-0 w-10 text-xs text-gray-400">{{ $lbl }}</span>
        <input type="text" wire:model="{{ $a }}" inputmode="numeric" maxlength="2"
               class="w-10 text-center border border-gray-300 rounded-lg py-1.5 text-sm font-bold font-mono focus:outline-none focus:ring-2 focus:ring-green-500{{ $opc ? ' text-gray-400' : '' }}"
               placeholder="{{ $opc ? '–' : '0' }}">
        <span class="w-6 text-center font-bold text-gray-400 select-none">—</span>
        <input type="text" wire:model="{{ $b }}" inputmode="numeric" maxlength="2"
               class="w-10 text-center border border-gray-300 rounded-lg py-1.5 text-sm font-bold font-mono focus:outline-none focus:ring-2 focus:ring-green-500{{ $opc ? ' text-gray-400' : '' }}"
               placeholder="{{ $opc ? '–' : '0' }}">
        @if($opc)<span class="text-xs text-gray-400">opc.</span>@endif
    </div>
    @endforeach
</div>
