{{--
    The call stack a debug-level message is reported with, drawn inside an error box rather than
    as a page of its own.

    The app's own frames are filled and marked; everything under /vendor/ is dimmed rather than
    dropped, because a stack with its hops removed reads as if the remaining frames called each
    other directly. Consecutive frames share one filled block so the fill reads as "this stretch is
    ours" rather than as a border per call, and a long run of vendor frames starts folded — the
    hops are worth keeping, but not worth the screen they take before anybody asks.
--}}
<div class="mt-2 text-left font-mono text-[11px] leading-normal"
     x-collapse>
    @foreach ($groups as $group)
        @php $fold = $group['vendor'] && count($group['frames']) > 2; @endphp

        @if ($fold)
            <div x-collapse:item="vendor-{{ $loop->index }}">
                <button type="button"
                        class="x-collapse__trigger flex w-full items-center gap-2 px-2 py-1 text-start text-[#aaaaaa] opacity-40"
                        x-collapse:trigger>
                    {{ count($group['frames']) }} frames from vendor
                </button>

                <div class="x-collapse__panel opacity-40" x-collapse:panel>
                    @foreach ($group['frames'] as $frame)
                        <div class="flex gap-2.5 border-l-2 border-transparent px-2 py-1">
                            @include('errors.partials.backtrace-frame', ['frame' => $frame])
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div @class([
                'border-l-2',
                'border-[#cc3300] bg-[#cc3300]/5' => ! $group['vendor'],
                'border-transparent opacity-40' => $group['vendor'],
            ])>
                @foreach ($group['frames'] as $frame)
                    <div class="flex gap-2.5 px-2 py-1">
                        @include('errors.partials.backtrace-frame', ['frame' => $frame])
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
</div>
