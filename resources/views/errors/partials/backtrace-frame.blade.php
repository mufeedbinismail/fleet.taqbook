<span class="w-[26px] shrink-0 pt-px text-[10px] text-backtrace-index-txt">#{{ $frame['index'] }}</span>

<div class="min-w-0 flex-1">
    {{-- A frame PHP raised from inside itself has no file at all, and there is nothing to say about
         where it was — only the call is written for those. --}}
    @if ($frame['file'] !== null)
        <div class="text-backtrace-path-txt">{{ $frame['dir'] }}/<span
                class="font-semibold text-backtrace-file-txt">{{ $frame['file'] }}</span>@if ($frame['line'] !== null)<span
                class="font-semibold text-backtrace-line-txt">:{{ $frame['line'] }}</span>@endif</div>
    @endif

    <div class="text-backtrace-call-txt">{{ $frame['call'] }}()</div>
</div>
