@props(['label'])

<div {{ $attributes->class('x-stat') }}>
    <p class="x-stat__label">{{ $label }}</p>
    <p class="x-stat__value">{{ $slot }}</p>
    @isset($caption)
        <p {{ $caption->attributes->class('x-stat__caption') }}>{{ $caption }}</p>
    @endisset
</div>
