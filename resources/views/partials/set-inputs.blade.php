{{-- Inputs de sets con guión fijo --}}
{{-- Variables esperadas: $j1nombre, $j2nombre (strings) --}}
<div>
    <div class="space-y-2">
        <div class="flex items-center gap-2 pl-12">
            <span class="w-12 text-center text-xs font-semibold text-gray-600 truncate">{{ $j1nombre }}</span>
            <span class="w-6"></span>
            <span class="w-12 text-center text-xs font-semibold text-gray-600 truncate">{{ $j2nombre }}</span>
        </div>
        @foreach([['s1j1','s1j2','Set 1',false], ['s2j1','s2j2','Set 2',false], ['s3j1','s3j2','Set 3',true]] as [$a,$b,$label,$opcional])
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400 w-10 shrink-0">{{ $label }}</span>
            <input type="text" wire:model="{{ $a }}"
                   inputmode="numeric" maxlength="2"
                   class="w-12 text-center border border-gray-300 rounded-lg py-1.5 text-sm font-mono font-bold focus:outline-none focus:ring-2 focus:ring-green-500{{ $opcional ? ' text-gray-400' : '' }}"
                   placeholder="{{ $opcional ? '–' : '0' }}">
            <span class="font-bold text-gray-400 select-none w-6 text-center">—</span>
            <input type="text" wire:model="{{ $b }}"
                   inputmode="numeric" maxlength="2"
                   class="w-12 text-center border border-gray-300 rounded-lg py-1.5 text-sm font-mono font-bold focus:outline-none focus:ring-2 focus:ring-green-500{{ $opcional ? ' text-gray-400' : '' }}"
                   placeholder="{{ $opcional ? '–' : '0' }}">
            @if($opcional)
                <span class="text-xs text-gray-400">opc.</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
