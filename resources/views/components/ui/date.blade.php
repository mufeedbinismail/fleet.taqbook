@props([
    // Optional: a field that drives a screen rather than posting to one carries no name at all.
    'name' => null,
    'value' => null,
    'format' => null,
    'time' => false,

    // A time of day and no date.
    'timeOnly' => false,
    'min' => null,
    'max' => null,
    'highlight' => [],
    'disable' => [],
    'clearable' => false,
    'today' => false,
    'firstDay' => null,
    'readonly' => false,
    'disabled' => false,
    'placeholder' => null,
    'panelParent' => null,

    // Drawn open, in the flow, and never dismissed.
    'inline' => false,
])

@php
    use App\Foundation\Component\Support\Control;
    use App\Foundation\Shared\ValueObject\DomainDateTime;

    // A format of its own is for a field that genuinely differs.
    [$pattern, $read] = match (true) {
        $timeOnly => [$format ?? DomainDateTime::userTimeFormat(), DomainDateTime::readTime(...)],
        $time => [$format ?? DomainDateTime::userDateTimeFormat(), DomainDateTime::readDateTime(...)],
        default => [$format ?? DomainDateTime::userDateFormat(), DomainDateTime::readDate(...)],
    };

    $shown = $read($value, $format)?->format($pattern) ?? '';

    $carriesTime = $timeOnly || $time;
    $carriesDate = ! $timeOnly;

    $id = Control::id($attributes, 'date', $name);

    // Only what this field alone has to say; anything the page already said is left out.
    //
    // `time` rather than the format itself where the format is only the preference read one way
    // instead of the other: which reading is wanted is the field's to say, what it spells is not.
    $config = [
        'format' => $format,
        'time' => $format === null && $carriesTime && $carriesDate ?: null,
        'timeOnly' => $format === null && $carriesTime && ! $carriesDate ?: null,
        'min' => $min,
        'max' => $max,
        'highlight' => $highlight ?: null,
        'disable' => $disable ?: null,
        'clearable' => $clearable ?: null,
        'today' => $today ?: null,
        // Written only where this field disagrees with the page.
        'firstDay' => $firstDay !== null && $firstDay !== user_settings()->weekStart()->value ? $firstDay : null,
        'readonly' => $readonly ?: null,
        'panelParent' => $panelParent,
        'inline' => $inline ?: null,
    ];
@endphp

{{-- Data the field reads rather than an expression it evaluates, so it travels escaped rather
     than as script. --}}
<input
    type="text"
    @if ($name !== null) name="{{ $name }}" @endif
    id="{{ $id }}"
    value="{{ $shown }}"
    autocomplete="off"
    @if ($placeholder !== null) placeholder="{{ $placeholder }}" @endif
    @readonly($readonly)
    @disabled($disabled)
    x-date
    data-date="{{ Control::config($config) }}"

    {{-- Whether there is a clock to leave room for, which is the whole of what this decides about
         the field's own appearance. Merged into the class list rather than written as a second
         class attribute, of which only the first would be kept; a modifier and not a utility, so a
         stated width still overrules it. --}}
    {{ $attributes->except('id')->class([
        'x-date--with-time' => $carriesTime && $carriesDate,
        'x-date--time-only' => $carriesTime && ! $carriesDate,
    ]) }}
>
