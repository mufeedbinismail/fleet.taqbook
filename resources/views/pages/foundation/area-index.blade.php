@extends('layout.app')
@use('App\Foundation\Navigation\Enum\Column')
@php
/**
 * The two columns of one card, each a list of runs.
 *
 * Runs are compacted rather than indexed, so a run the user was refused every entry of is not in
 * the result at all — which is what makes a break something drawn between two runs that both
 * survived, and leaves no leading, trailing or doubled one representable.
 *
 * Rank settles the order within a run, and it is already the order these arrived in.
 */
$lay = function ($items) {
    $columns = array_fill_keys(array_column(Column::cases(), 'value'), []);

    foreach ($items as $item) {
        $placement = $item->declaration->placement;
        $columns[$placement->column->value][$placement->cluster][] = $item;
    }

    return array_map(function (array $runs) {
        ksort($runs);

        return array_values($runs);
    }, $columns);
};

// Everything the area lists, minus everything a section already carries. What is left hangs
// straight off the area, and would go undrawn without a word if nothing gathered it.
$grouped = $area->sections()->flatMap(fn ($group) => $group->items->all())->all();
$loose = $area->children()->reject(fn ($child) => in_array($child, $grouped, strict: true));

// The unlabelled block goes first: it is not inside any of the headings below it, so putting it
// after one would read as belonging to that heading.
$blocks = $loose->isEmpty() ? [] : [['label' => null, 'columns' => $lay($loose)]];

foreach ($area->sections() as $group) {
    $blocks[] = ['label' => $group->section->label->text(), 'columns' => $lay($group->items)];
}
@endphp

@section('content')
    {{--
        The second way to read a tree: one card per section, laid out across the page rather than
        down a column. What an entry is, what it wears and where it goes are all left to the entry
        itself — all this page decides is shape.

        Accelerators are withheld: a page holding every entry of an area at once would put more than
        one claim on some of the keys.
    --}}
    <div class="area-index mx-auto p-2 md:p-4">
        @foreach ($blocks as $block)
            <section class="area-index-card bg-white text-primary-txt shadow-md rounded-lg mb-4">
                @if ($block['label'] !== null)
                    <div class="bg-card-header-bg text-card-header-txt p-2 md:p-4 rounded-t-lg">
                        <h2 class="text-lg font-semibold">{{ $block['label'] }}</h2>
                    </div>
                @endif

                <div class="p-2 md:p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($block['columns'] as $column => $runs)
                        {{--
                            The left half is drawn whatever is on it, so a card with entries on the
                            right alone still puts them on the right. The right half is drawn only
                            when it has something, which is what stops an empty one from claiming
                            width beside a column that could have used it.
                        --}}
                        @if ($column === Column::Left->value || $runs !== [])
                            <div class="area-index-column col-span-1 p-2 md:p-4 grid grid-cols-1">
                                @foreach ($runs as $run)
                                    @foreach ($run as $item)
                                        <x-nav::entry :node="$item" :accelerated="false" class="col-span-1" />
                                    @endforeach

                                    @unless ($loop->last)
                                        <div class="area-index-break col-span-1" aria-hidden="true">&nbsp;</div>
                                    @endunless
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection
