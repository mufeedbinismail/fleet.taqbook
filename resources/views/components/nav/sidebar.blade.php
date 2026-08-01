@props(['navigation', 'location'])
{{--
    The whole tree, not a list of ways into it. Everything handed here is already reachable by the
    user it was resolved for, so there is no access test in this file and no branch for an entry
    that cannot be opened.

    One area is open at a time, and which one is not named here. It is read off the markup, so there
    is no second statement of it in this file to disagree with what the page is already showing, and
    no moment on load where the menu is drawn some other way and then corrected.
--}}
<nav {{ $attributes->class('main-nav') }} x-accordion>
    <ul>
        @foreach ($navigation->areas() as $area)
            <x-nav.area :area="$area" :location="$location" />
        @endforeach
    </ul>
</nav>
