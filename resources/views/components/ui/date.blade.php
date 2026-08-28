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
    use App\Foundation\Component\Date\Constant\DateReading;
    use App\Foundation\Component\Date\Constant\DateToken;
    use App\Foundation\Component\Support\Control;
    use App\Foundation\Shared\ValueObject\DomainDateTime;

    // Naming a format is for a field that genuinely differs; the ordinary case says nothing and
    // takes the page's default.
    $pattern = $format ?? match (true) {
        $timeOnly => DomainDateTime::userTimeFormat(),
        $time => DomainDateTime::userDateTimeFormat(),
        default => DomainDateTime::userDateFormat(),
    };

    // Every date handed to this component is read the same way, whether it arrives as a date or
    // as text in either spelling.
    $shown = DateReading::read($value, $pattern)?->format($pattern) ?? '';

    $carriesTime = DateToken::carriesTime($pattern);
    $carriesDate = DateToken::carriesDate($pattern);

    $id = Control::id($attributes, 'date', $name);

    // Only what this field alone has to say; anything the page already said is left out.
    //
    // `time` rather than the format itself where the format is only the preference read one way
    // instead of the other: which reading is wanted is the field's to say, what it spells is not.
    $config = [
        'format' => $format,
        'time' => $format === null && $carriesTime && $carriesDate ?: null,
        'timeOnly' => $format === null && $carriesTime && ! $carriesDate ?: null,
        // Read here rather than sent as they arrived, so a bound and a mark answer to the same
        // spellings the value does.
        'min' => DateReading::day($min, $pattern),
        'max' => DateReading::day($max, $pattern),
        'highlight' => DateReading::days($highlight, $pattern) ?: null,
        'disable' => DateReading::days($disable, $pattern) ?: null,
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
