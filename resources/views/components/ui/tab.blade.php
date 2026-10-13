@props(['name', 'label'])

@aware(['id'])

@php
    // `id` is what the nearest enclosing component wrote on its tag, so a row inside some other component that has an `id` is not caught.
    if ($id === null) {
        throw new LogicException('<x-ui.tab name="'.$name.'"> must be placed inside <x-ui.tabs>.');
    }
@endphp

@push("tabs:{$id}:names")
{{ $name }}
@endpush

@push("tabs:{$id}:strip")
<button x-tabset:tab="{{ $name }}" data-tab="{{ $name }}">{{ $label }}</button>
@endpush

@push("tabs:{$id}:panels")
<div x-tabset:panel="{{ $name }}" data-panel="{{ $name }}">
    <template x-tabset:lazy>
        <div class="x-tabs__content">{{ $slot }}</div>
    </template>
</div>
@endpush
