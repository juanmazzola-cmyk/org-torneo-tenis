{{-- Inputs de sets con guión fijo --}}
{{-- Variables esperadas: $j1nombre, $j2nombre (strings) --}}
<div>
    <div class="flex items-center text-xs font-medium text-gray-500 mb-1 pl-12">
        <span class="w-14 text-center truncate">{{ $j1nombre }}</span>
        <span class="mx-3"></span>
        <span class="w-14 text-center truncate">{{ $j2nombre }}</span>
    </div>
    <div class="space-y-2">
        @foreach([['s1j1','s1j2','Set 1'], ['s2j1','s2j2','Set 2'], ['s3j1','s3j2','Set 3']] as [$a, $b, $label])
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400 w-10 shrink-0">{{ $label }}</span>
            <input type="text" wire:model="{{ $a }}"
                   inputmode="numeric" maxlength="2"
                   class="w-14 text-center border border-gray-300 rounded-lg py-2 text-sm font-mono font-bold focus:outline-none focus:ring-2 focus:ring-green-500
                          {{ $label === 'Set 3' ? 'text-gray-400 placeholder-gray-300' : '' }}"
                   placeholder="{{ $label === 'Set 3' ? '-' : '0' }}">
            <span class="font-bold text-gray-400 text-lg select-none">—</span>
            <input type="text" wire:model="{{ $b }}"
                   inputmode="numeric" maxlength="2"
                   class="w-14 text-center border border-gray-300 rounded-lg py-2 text-sm font-mono font-bold focus:outline-none focus:ring-2 focus:ring-green-500
                          {{ $label === 'Set 3' ? 'text-gray-400 placeholder-gray-300' : '' }}"
                   placeholder="{{ $label === 'Set 3' ? '-' : '0' }}">
            @if($label === 'Set 3')
                <span class="text-xs text-gray-400">opcional</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
