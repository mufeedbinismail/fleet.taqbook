<?php

namespace App\Foundation\Navigation\Service;

use App\Foundation\Navigation\Entity\Node;
use App\Foundation\Navigation\Facade\Navigation;
use App\Foundation\Navigation\ValueObject\CurrentLocation;
use App\Foundation\Navigation\ValueObject\NavigationTree;
use App\Foundation\Navigation\ValueObject\RouteTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Works out which node a request is on, and hands back the answer as a value.
 *
 * Three strategies in order, first answer wins. Nothing here reads session state: a location is
 * derivable from the request or declared by the page serving it, never inherited from whatever was
 * clicked earlier. That is the whole of the fix for a highlight that used to survive navigating
 * away from the thing it highlighted.
 *
 *   1. the page said so
 *   2. a named route claimed it
 *   3. some declared address claimed it, most specific winning
 *
 * A named route outranks an address even when the address makes the more detailed claim, because a
 * route name is an identity while an address is a description that happens to fit.
 *
 * Only the tree resolved for this user is searched, so a location can never come back naming
 * somewhere the user cannot go.
 *
 * Stateless, and holds nothing between calls: everything the answer depends on arrives as an
 * argument, which is what lets the answer be cached by whoever wanted it rather than here.
 */
class LocationResolver
{
    public function resolve(NavigationTree $tree, Request $request): CurrentLocation
    {
        return new CurrentLocation(
            $this->place($tree, $request),
            $request->attributes->get(Navigation::CRUMBS, []),
        );
    }

    private function place(NavigationTree $tree, Request $request): ?Node
    {
        $declared = $request->attributes->get(Navigation::DECLARED);

        // A page may name somewhere this user cannot reach, in which case the address it arrived at
        // is a better answer than none.
        if ($declared !== null && $tree->has($declared)) {
            return $tree->find($declared);
        }

        return $this->claimed($tree, $request, routesOnly: true)
            ?? $this->claimed($tree, $request, routesOnly: false)
            ?? $this->unresolved($request, $declared);
    }

    /**
     * The node whose target makes the strongest claim on the request. Ties go to whichever was
     * declared first, which the tree's index already orders.
     */
    private function claimed(NavigationTree $tree, Request $request, bool $routesOnly): ?Node
    {
        $best = null;
        $rank = 0;

        foreach ($tree->all() as $node) {
            $target = $node->declaration->target;

            if ($target === null || ($routesOnly && ! $target instanceof RouteTarget)) {
                continue;
            }

            try {
                $claim = $target->matches($request);
            } catch (Throwable) {
                // A target too broken to answer forfeits its claim rather than the whole lookup.
                continue;
            }

            if ($claim !== null && $claim > $rank) {
                $best = $node;
                $rank = $claim;
            }
        }

        return $best;
    }

    /**
     * Named rather than silent, so crawling the application produces an inventory of everything
     * still unmapped instead of a page that quietly renders without a trail.
     */
    private function unresolved(Request $request, ?string $declared): ?Node
    {
        Log::warning('Navigation could not place a request.', [
            'path' => $request->path(),
            'query' => $request->getQueryString(),
            'declared' => $declared,
        ]);

        return null;
    }
}
