@props(['color' => null])

@php
    /*
        What arrived decides how it is worn: a variant name is a meaning the stylesheet resolves,
        a CSS color is taken as given, anything else is a class of the caller's own — and nothing
        at all is the neutral base look, which is what lets a caller bind the color per item on
        the client instead of saying one here.

        A color is known by its written form — #, a functional form, or var() — because a bare
        keyword cannot be told from a class name. A caller who means the keyword writes it as one
        of those forms; written bare, it travels as a class.
    */
    $variants = ['success', 'warning', 'danger', 'info', 'neutral'];

    if ($color === null) {
        $attributes = $attributes->class(['x-badge']);
    } elseif (in_array($color, $variants, true)) {
        $attributes = $attributes->class(['x-badge', 'x-badge--'.$color]);
    } elseif (preg_match('/^(#|rgb|hsl|oklch|var\()/', $color) === 1) {
        $attributes = $attributes->class(['x-badge'])->merge(['style' => '--badge-color: '.$color]);
    } else {
        $attributes = $attributes->class(['x-badge', $color]);
    }
@endphp

<span {{ $attributes }}>{{ $slot }}</span>
