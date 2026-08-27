<span class="w-[26px] shrink-0 pt-px text-[10px] text-[#aaaaaa]">#{{ $frame['index'] }}</span>

<div class="min-w-0 flex-1">
    {{-- A frame PHP raised from inside itself has no file at all, and there is nothing to say about
         where it was — only the call is written for those. --}}
    @if ($frame['file'] !== null)
        <div class="text-[#777777]">{{ $frame['dir'] }}/<span
                class="font-semibold text-[#333333]">{{ $frame['file'] }}</span>@if ($frame['line'] !== null)<span
                class="font-semibold text-[#cc3300]">:{{ $frame['line'] }}</span>@endif</div>
    @endif

    <div class="text-[#222222]">{{ $frame['call'] }}()</div>
</div>
