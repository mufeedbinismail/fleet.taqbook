@props(['variant' => 'primary', 'icon' => null])

@php
$variants = [
    'primary' => 'border-0 bg-button-primary-bg text-button-primary-txt hover:bg-button-primary-hover-bg',
    'outline' => 'border border-button-outline-border bg-transparent text-button-outline-txt hover:bg-button-outline-hover-bg',
    'danger' => 'border border-button-danger-border bg-transparent text-button-danger-txt hover:bg-button-danger-hover-bg',
];
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'inline-flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition'
        .' disabled:cursor-not-allowed disabled:opacity-50 '.$variants[$variant],
]) }}>
    @if ($icon)
        <span class="icon icon-{{ $icon }}"></span>
    @endif
    {{ $slot }}
</button>
