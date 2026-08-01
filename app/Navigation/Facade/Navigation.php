<?php

namespace App\Navigation\Facade;

use App\Navigation\Contract\Label;
use App\Navigation\Contract\NavigationSource;
use App\Navigation\Entity\Node;
use App\Navigation\Exception\LocationAlreadyResolvedException;
use App\Navigation\Registry\SourceRegistry;
use App\Navigation\ValueObject\Crumb;
use App\Navigation\ValueObject\CurrentLocation;
use App\Navigation\ValueObject\NavigationTree;
use App\Navigation\ValueObject\Trail;

/**
 * Registration, location and the tree — globals because of where they are asked for. Registering
 * happens before there is anything to inject into, and the rest are reached from legacy scripts,
 * which have no constructor to receive them any other way and no wiring of their own.
 *
 * Anything the container builds should take what it needs as a dependency instead of reaching here.
 *
 * The calls are declared here rather than delegated to a root object, so nothing is held between
 * them. Registration is a boot-time concern and location a request-scoped one; resolving each only
 * at the moment it is asked for is what lets one global span both without a reference to either
 * outliving its own scope.
 *
 * Deliberately not an Illuminate facade. A declared static never reaches __callStatic, so the base
 * class could deliver none of what it is normally chosen for; rebind the container to substitute
 * one of these.
 */
final class Navigation
{
    /**
     * Where a page leaves its own claim about where it is, and the steps it wants on the trail.
     *
     * Both ride on the request because that is the one thing whose lifetime already matches: one
     * page, one answer, discarded together, with nothing to reset between requests.
     */
    public const DECLARED = 'navigation.declared';

    public const CRUMBS = 'navigation.crumbs';

    /**
     * @param  class-string<NavigationSource>|NavigationSource  ...$sources
     */
    public static function register(string|NavigationSource ...$sources): SourceRegistry
    {
        return app(SourceRegistry::class)->register(...$sources);
    }

    /**
     * A page naming its own location, for when the request cannot answer — two modes of one script
     * telling themselves apart by something only a lookup knows, say.
     *
     * @throws LocationAlreadyResolvedException if anything has already asked where this request is
     */
    public static function here(string $key): void
    {
        if (app()->resolved(CurrentLocation::class)) {
            throw LocationAlreadyResolvedException::declaring($key);
        }

        request()->attributes->set(self::DECLARED, $key);
    }

    /**
     * Adds a step naming whatever record this page is showing. The sitemap cannot supply one: it is
     * built once at boot and this is known only per request.
     *
     * @throws LocationAlreadyResolvedException if anything has already asked where this request is
     */
    public static function crumb(Crumb|Label|string $crumb): void
    {
        if (app()->resolved(CurrentLocation::class)) {
            throw LocationAlreadyResolvedException::appending();
        }

        $request = request();

        $request->attributes->set(self::CRUMBS, [
            ...$request->attributes->get(self::CRUMBS, []),
            $crumb instanceof Crumb ? $crumb : Crumb::of($crumb),
        ]);
    }

    public static function current(): ?Node
    {
        return self::location()->node;
    }

    public static function trail(): Trail
    {
        return self::location()->trail();
    }

    public static function location(): CurrentLocation
    {
        return app(CurrentLocation::class);
    }

    public static function tree(): NavigationTree
    {
        return app(NavigationTree::class);
    }
}
