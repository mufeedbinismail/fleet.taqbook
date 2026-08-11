@props([
    'name',
    'title' => null,
    'size' => 'md',
    'transition' => 'none',
    'static' => false,
    'dismissible' => true,
])

@php
    /*
            <button x-modal:open="{ name: 'user-editor', with: row }">Edit</button>

            <x-ui.modal name="user-editor" :title="__('foundation.user.edit')" size="lg" static
                        @modal:showing="edit($event.detail)">
                ...fields...
                <x-slot:actions>
                    <x-ui.button @click="save()">{{ __('foundation.user.action.save') }}</x-ui.button>
                </x-slot>
            </x-ui.modal>

        size: sm | md | lg | xl | full. transition: none | zoom | fade | slide.
        static withholds Escape and the backdrop click; dismissible carries the close button.

        The `header` slot stands in for the title where the band needs markup rather than a word.
        Either one names the modal to whoever is read it out; a modal drawn with neither says so
        itself with an `aria-label`, which reaches the dialog like any other attribute.
    */

    /*
        Written out whole rather than assembled from the prop, and refused by name where the prop is
        not one of them. A class built up from a variable is one nothing has seen written down —
        Tailwind never emits it — so a mistyped word would otherwise reach the page as a modal that
        opens looking broken, with nothing anywhere saying which word was not a size.
    */
    $sizes = [
        'sm' => 'x-modal--sm',
        'md' => 'x-modal--md',
        'lg' => 'x-modal--lg',
        'xl' => 'x-modal--xl',
        'full' => 'x-modal--full',
    ];

    // `fade` carries no class: opacity is what every modal already animates, so naming it would
    // declare nothing. It is a value so a page can say the plain one was what it meant.
    $transitions = [
        'none' => 'x-modal--none',
        'fade' => '',
        'zoom' => 'x-modal--zoom',
        'slide' => 'x-modal--slide',
    ];

    $sizeClass = $sizes[$size]
        ?? throw new \InvalidArgumentException("Unknown modal size [{$size}].");

    $transitionClass = $transitions[$transition]
        ?? throw new \InvalidArgumentException("Unknown modal transition [{$transition}].");

    $labelId = $title === null && ! isset($header) ? null : $name.'-title';
@endphp

<dialog
    {{ $attributes->merge(['class' => rtrim("x-modal {$sizeClass} {$transitionClass}")]) }}
    @if ($static) x-modal.static="@js($name)" @else x-modal="@js($name)" @endif
    @if ($labelId !== null) aria-labelledby="{{ $labelId }}" @endif
>
    @if ($labelId !== null || $dismissible)
        <header class="x-modal__head">
            @if ($labelId !== null)
                <h2 class="x-modal__title" id="{{ $labelId }}">{{ $header ?? $title }}</h2>
            @endif

            @if ($dismissible)
                <button type="button" class="x-modal__close" x-modal:close
                        aria-label="{{ __('foundation.modal.close') }}"></button>
            @endif
        </header>
    @endif

    <div class="x-modal__body">
        {{ $slot }}
    </div>

    @isset($actions)
        <footer class="x-modal__foot">
            {{ $actions }}
        </footer>
    @endisset
</dialog>
