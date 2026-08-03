@props(['variant' => 'primary', 'icon' => null])

@php
$variants = [
    'primary' => 'border-0 bg-primary-accent text-white hover:opacity-90',
    'outline' => 'border border-soft-border bg-transparent text-primary-txt hover:bg-label-bg',
    'danger' => 'border border-soft-border bg-transparent text-secondary-accent hover:bg-label-bg',
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
