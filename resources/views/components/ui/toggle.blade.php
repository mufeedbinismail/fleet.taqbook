@props([
    'variant' => null,
    'name' => null,
    'checked' => false,
    'on' => [],
    'off' => [],
    'disabled' => false,
    'readonly' => false,
])

@php
    use App\Foundation\Component\Control\Support\Control;

    /*
        Grouped by state rather than by property: it is the only shape in which the value and the
        word for one state cannot drift apart from each other.

        Only what a caller chose travels. The two default words are not written into it: stated
        here as well as where they are drawn, they would be two defaults with nothing holding them
        together. `false` is a different statement from silence — it asks for no word, so it is kept
        where a null is dropped.
    */
    $states = collect(['on' => $on, 'off' => $off])->map(fn ($state, $which) => (object) array_filter(
        [
            // Only an element that can hold a value carries one. A caller names the pair only for
            // a column that disagrees with what a flag has always been stored as.
            'value' => $name === null
                ? null
                : (string) (data_get($state, 'value') ?? ($which === 'on' ? '1' : '0')),
            'label' => data_get($state, 'label'),
            'icon' => data_get($state, 'icon'),
        ],
        fn ($one) => $one !== null,
    ));

    /*
        What the clerk last set outranks what the record holds: a bounced save comes back as a fresh
        render off the record, so a flag they changed and saw reset by the screen that asked them to
        try again is undone by the resave without anybody being told.
    */
    $held = $name !== null && old($name) !== null
        ? (string) old($name) === $states['on']->value
        : (bool) $checked;

    // The word a row is read by where no script has run to draw one. A refusal leaves it wordless,
    // which is all "the switch alone" can mean before there is a switch.
    $word = fn ($which) => ($label = $states[$which]->label ?? null) === false
        ? ''
        : ($label ?? __('foundation.toggle.'.$which));

    $data = Control::config($states->all());

    // A name that is not one of the known variants travels as a class of the caller's own, so a
    // screen with a look of its own reaches the same properties without one being added here first.
    $look = in_array($variant, ['primary', 'success', 'warning', 'danger', 'neutral'], true)
        ? 'x-toggle--'.$variant
        : $variant;
@endphp

@if ($name === null)
    {{-- So the switch is drawn the right way round on the first paint, not corrected on the next
         one. --}}
    <button
        type="button"
        x-toggle
        data-toggle="{{ $data }}"

        {{-- A switch posting nothing has no value to keep, so the only thing readonly can mean
             here is what disabled already means. --}}
        @disabled($disabled || $readonly)
        {{ $attributes->class(['x-toggle', $look => $look, 'x-toggle--on' => $held]) }}
    ></button>
@else
    {{-- Off is a value of its own rather than the absence of one, which is the whole difference
         between a flag somebody turned off and a flag nobody sent. --}}
    <select
        @if (! $readonly) name="{{ $name }}" @endif
        id="{{ Control::id($attributes, 'toggle', $name) }}"
        x-toggle
        data-toggle="{{ $data }}"
        @disabled($disabled || $readonly)

        {{-- No appearance is written here: every class on this element is carried onto the switch
             that replaces it, where a utility outranks the component layer — so a default written
             here would be the one thing the stylesheet could not overrule. --}}
        {{ $attributes->except('id')->class([$look => $look]) }}
    >
        <option value="{{ $states['off']->value }}" @selected(! $held)>{{ $word('off') }}</option>
        <option value="{{ $states['on']->value }}" @selected($held)>{{ $word('on') }}</option>
    </select>

    {{-- A <select> has no readonly of its own, and a disabled one is not submitted at all. Without
         something else carrying the value, the legacy side sees no field, reads that as no change,
         and leaves whatever was there standing — which is not what the screen showed. --}}
    @if ($readonly)
        <input type="hidden" name="{{ $name }}" value="{{ $held ? $states['on']->value : $states['off']->value }}">
    @endif
@endif
