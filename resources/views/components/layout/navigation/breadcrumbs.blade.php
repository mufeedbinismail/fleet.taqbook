@props(['location'])
@php
$trail = $location->trail();
@endphp
{{--
    The step you are standing on is drawn as text even when it knows its own address, so the trail
    never offers a way to where you already are. Every earlier step links if it can; a step naming a
    record carries no address at all and so never does.
--}}
@if (count($trail) > 0)
    <nav {{ $attributes->class('breadcrumbs') }} aria-label="{{ __('Breadcrumb') }}">
        <ol>
            @foreach ($trail->crumbs() as $crumb)
                <li>
                    @if ($crumb->url !== null && ! $loop->last)
                        <a href="{{ $crumb->url }}">{{ $crumb->label->text() }}</a>
                    @elseif ($loop->last)
                        <span aria-current="page">{{ $crumb->label->text() }}</span>
                    @else
                        <span>{{ $crumb->label->text() }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
