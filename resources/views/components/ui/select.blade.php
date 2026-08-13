@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'multiple' => false,
    'searchable' => true,
    'clearable' => null,
    'disabled' => false,
    'readonly' => false,
    'url' => null,
    'params' => [],
    'paramSources' => [],
    'perPage' => \App\Foundation\Component\Select\Intent\OptionSearchIntent::PER_PAGE,
    'minSearch' => 0,
    'clearOnParamChange' => false,
    'panelParent' => null,
    'valueField' => 'value',
    'labelField' => 'label',
    'descriptionField' => 'description',
    'disabledField' => 'disabled',
    'groupField' => 'group',
    'dataField' => 'data',
])

@php
    use App\Foundation\Component\Support\Control;

    // Options are flattened here rather than in the browser, so whatever shape a screen already has
    // its rows in — models, arrays, a value-keyed map — reaches the control as one shape and the
    // field names are needed in exactly one place.
    $rows = collect($options)->map(function ($row, $key) use ($valueField, $labelField, $descriptionField, $disabledField, $groupField, $dataField) {
        if (! is_array($row) && ! is_object($row)) {
            return ['value' => (string) $key, 'label' => (string) $row, 'description' => null, 'disabled' => false, 'group' => null, 'data' => []];
        }

        $value = data_get($row, $valueField);

        return [
            'value' => (string) $value,
            'label' => (string) (data_get($row, $labelField) ?? $value),
            'description' => data_get($row, $descriptionField),
            'disabled' => (bool) data_get($row, $disabledField, false),
            'group' => data_get($row, $groupField),

            // Held to the same names and the same flattening a fetched row is, so a control seeded
            // from the server and one that has since re-fetched carry their extras identically.
            'data' => \App\Foundation\Component\Select\Support\DataAttributes::of(data_get($row, $dataField)),
        ];
    })->values();

    // Grouped rows are written inside the group that names them, because that is where a select
    // keeps them — and where the control reads them back from when no dataset was handed over.
    $grouped = $rows->groupBy(fn ($row) => $row['group'] ?? '');

    $held = collect($selected)->map(fn ($value) => (string) $value)->all();

    $id = Control::id($attributes, 'select', $name);

    $config = [
        'multiple' => $multiple,
        'searchable' => $searchable,
        'clearable' => $clearable,
        'placeholder' => $placeholder,
        'readonly' => $readonly,
        'url' => $url === null ? null : (\Illuminate\Support\Facades\Route::has($url) ? route($url) : url($url)),
        'params' => (object) $params,

        // Written only where a screen named one, so a control that narrows by nothing carries no
        // word about narrowing at all.
        'paramSources' => $paramSources ? (object) $paramSources : null,
        'perPage' => $perPage,
        'minSearch' => $minSearch,
        'clearOnParamChange' => $clearOnParamChange,
        'panelParent' => $panelParent,
    ];
@endphp

{{-- The configuration is data the control reads, not an expression it evaluates, so it travels as
     an escaped attribute rather than as script. That keeps a page carrying twenty selects from
     parsing twenty snippets of JavaScript before any of them can be used. --}}
<select
    name="{{ $name }}"
    id="{{ $id }}"
    @if ($multiple) multiple @endif
    @disabled($disabled)
    x-select
    data-select="{{ Control::config($config) }}"

    {{-- Nothing of the control's own appearance is written here. Every class on this element is
         copied onto the control that replaces it, and a utility outranks the component layer that
         draws that control — so a default written here would be the one thing the stylesheet
         could not overrule, and the two ways of reaching this control would not look alike. --}}
    {{ $attributes->except('id') }}
>
    {{-- A single select with nothing chosen would otherwise show its first row as though somebody
         had picked it, and post that row's value on a form nobody touched. --}}
    @if (! $multiple && $placeholder !== null)
        <option value=""></option>
    @endif

    @foreach ($grouped as $group => $rows)
        @if ($group !== '')<optgroup label="{{ $group }}">@endif

        @foreach ($rows as $row)
            <option
                value="{{ $row['value'] }}"
                @selected(in_array($row['value'], $held, true))
                @disabled($row['disabled'])
                @if ($row['description']) data-description="{{ $row['description'] }}" @endif
                @foreach ($row['data'] as $key => $value) data-{{ $key }}="{{ $value }}" @endforeach
            >{{ $row['label'] }}</option>
        @endforeach

        @if ($group !== '')</optgroup>@endif
    @endforeach
</select>
