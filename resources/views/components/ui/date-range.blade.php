@props([
    // Named per end rather than derived from a name for the pair: what an end is called is a fact
    // about what it is posted to. Either may be left unnamed, for a pair that posts nowhere.
    'fromName' => null,
    'toName' => null,
    'from' => null,
    'to' => null,
    // What the pair is, in words — named once, because the two are one thing being asked for.
    'legend' => null,
    'legendVisible' => true,
    'fromLabel' => null,
    'toLabel' => null,
    'format' => null,
    'time' => false,
    // The window the whole period sits inside, which each end is held to as well as to the other.
    'min' => null,
    'max' => null,
    'highlight' => [],
    'disable' => [],
    'clearable' => true,
    'today' => false,
    'firstDay' => null,
    'readonly' => false,
    'disabled' => false,
    'panelParent' => null,
])

@php
    use App\Foundation\Component\Control\Support\Control;

    $id = Control::id($attributes, 'date-range', $fromName);

    $start = $fromLabel ?? __('foundation.date.range.from');
    $end = $toLabel ?? __('foundation.date.range.to');

    // Everything the period describes, passed to both ends unchanged. A bag rather than markup
    // written on each tag: a loop inside a component tag leaves Blade unable to read the tag, which
    // it then prints as text rather than reporting. Keyed as the end's own props are, a bag being
    // handed straight over without the camel-casing a tag is read with.
    $shared = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'format' => $format,
        'time' => $time ?: null,
        'min' => $min,
        'max' => $max,
        'highlight' => $highlight ?: null,
        'disable' => $disable ?: null,
        'clearable' => $clearable ?: null,
        'today' => $today ?: null,
        'firstDay' => $firstDay,
        'readonly' => $readonly ?: null,
        'disabled' => $disabled ?: null,
        'panelParent' => $panelParent,
    ], fn ($one) => $one !== null));
@endphp

{{-- A fieldset because a group of controls answering one question is one, and the legend is what
     names the question. What links the two ends is the directive, not anything either knows. --}}
<fieldset
    x-date-range
    {{ $attributes->except('id')->class('x-date-range') }}
    id="{{ $id }}"
>
    @if ($legend !== null)
        <legend class="x-date-range__legend {{ $legendVisible ? '' : 'sr-only' }}">{{ $legend }}</legend>
    @endif

    {{-- One box rather than two, the ends being halves of one answer. Which end each is, is said
         by the word standing in it while it is empty; a label above each half would separate
         them again. --}}
    <div class="x-date-range__fields">
        <label class="sr-only" for="{{ $id }}-from">{{ $start }}</label>

        <x-ui.date
            :id="$id.'-from'"
            :name="$fromName"
            :value="$from"
            :placeholder="$start"
            class="x-date-range__field"
            :attributes="$shared"
        />

        <label class="sr-only" for="{{ $id }}-to">{{ $end }}</label>

        <x-ui.date
            :id="$id.'-to'"
            :name="$toName"
            :value="$to"
            :placeholder="$end"
            class="x-date-range__field"
            :attributes="$shared"
        />
    </div>
</fieldset>
