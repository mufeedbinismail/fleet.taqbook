@props(['area', 'location'])
@php
$key = $area->key();
$current = $location->within($key);
$accessKey = $area->label()->accessKey();
$sections = $area->sections();

// Opening an area lands on something rather than on a column of shut headings: the section holding
// the entry the request is on, and failing that the first one, which is the section an area was
// written to be read from.
$openSection = $sections->first(
    fn ($group) => $group->items->contains(fn ($item) => $location->is($item->key())),
)?->section->key ?? $sections->first()?->section->key;
@endphp
{{--
    Going to an area and opening it are separate acts, so they are separate controls. Following the
    name leaves the page; the caret only rearranges the menu, and a user peering into another area
    does not lose the one they are working in.

    Sections open one at a time within the area, which is a second accordion and not a second state:
    the two nest, and shutting or opening a section leaves the area it is in alone.

    Both accordions are handed their opening state as each part's own open class — the item's on the
    item, the trigger's on the trigger, the panel's on the panel — which is the state before any
    script runs as much as it is the state after. So the menu is drawn once, already showing the way
    to the page being read. A part left out is a part that renders shut over an open panel and then
    snaps round the moment the page is bound.
--}}
<li @class(['nav__area', 'x-accordion__item--open' => $current]) x-accordion:item="{{ $key }}">
    <div @class(['nav__row nav__row--split', 'nav__row--current' => $current])>
        <a href="{{ $area->url() }}" @if ($accessKey !== null) accesskey="{{ $accessKey }}" @endif>
            <span class="icon {{ $area->icon() ?? 'icon-spacer' }}"></span>
            <span class="nav__row-label"><x-nav::label :label="$area->label()" /></span>
        </a>

        <button
            aria-label="{{ $area->label()->text() }}"
            @class(['x-accordion__trigger--open' => $current])
            x-accordion:trigger
        >
            <span class="x-accordion__caret" aria-hidden="true"></span>
        </button>
    </div>

    <div @class(['nav__panel', 'x-accordion__panel--open' => $current]) x-accordion:panel>
        <ul x-accordion>
            @foreach ($sections as $group)
                @php $sectionKey = $group->section->key; @endphp
                <li @class(['nav__section', 'x-accordion__item--open' => $sectionKey === $openSection]) x-accordion:item="{{ $sectionKey }}">
                    <button @class(['nav__row', 'x-accordion__trigger--open' => $sectionKey === $openSection]) x-accordion:trigger>
                        <span class="nav__row-label">{{ $group->section->label->text() }}</span>
                        <span class="x-accordion__caret" aria-hidden="true"></span>
                    </button>

                    <ul @class(['nav__panel', 'x-accordion__panel--open' => $sectionKey === $openSection]) x-accordion:panel>
                        @foreach ($group->items as $item)
                            <li>
                                <x-nav::entry
                                    :node="$item"
                                    :accelerated="$current"
                                    @class(['nav__row--current' => $location->is($item->key())])
                                />
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </div>
</li>
