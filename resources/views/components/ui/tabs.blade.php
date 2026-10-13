@props([
    'id',
    'label',
    'selected' => null,
    // The address parameter the chosen section is kept in; without one it is held in memory only.
    'param' => null,
    // Grows into the height of a flex parent, the open section taking what the strip leaves, in place of its own content's height.
    'fill' => false,
])

@php
    // The rows have already run by now, each leaving its name, its button and its panel under this id.
    $names = preg_split('/\s+/', trim($__env->yieldPushContent("tabs:{$id}:names")), -1, PREG_SPLIT_NO_EMPTY);

    if ($names === []) {
        throw new LogicException("<x-ui.tabs id=\"{$id}\"> has no <x-ui.tab> rows.");
    }

    if (count($names) !== count(array_unique($names))) {
        throw new LogicException("<x-ui.tabs id=\"{$id}\"> has two <x-ui.tab> rows with the same name.");
    }
@endphp

<div {{ $attributes->merge(['id' => $id])->class(['x-tabs--fill' => $fill]) }} x-tabset
     data-tabset="{{ json_encode(['param' => $param, 'selected' => $selected]) }}">
    <div x-tabset:list aria-label="{{ $label }}">
        {!! $__env->yieldPushContent("tabs:{$id}:strip") !!}
    </div>

    {!! $__env->yieldPushContent("tabs:{$id}:panels") !!}
</div>
