<?php

namespace App\Foundation\Navigation\View;

use App\Foundation\Navigation\ValueObject\CurrentLocation;
use App\Foundation\Navigation\ValueObject\NavigationTree;
use Illuminate\View\View;

/**
 * Hands a view the two things a menu needs: what there is to draw, and where the request is.
 *
 * Both are taken as constructor arguments, which pins this to the lifetime of whatever built it.
 * That is only safe while a fresh one is built per composition — registered as a singleton it would
 * answer every later request with the first one's location, and a menu would highlight a page
 * nobody is on.
 *
 * These two and nothing else. A view wanting the user, or anything else about the request, asks for
 * that on its own rather than receiving it because navigation happened to be composed nearby.
 */
class NavigationComposer
{
    public function __construct(
        private readonly NavigationTree $navigation,
        private readonly CurrentLocation $location,
    ) {}

    public function compose(View $view): void
    {
        $view->with([
            'navigation' => $this->navigation,
            'location' => $this->location,
        ]);
    }
}
