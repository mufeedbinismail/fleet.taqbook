<?php

namespace App\Foundation\Navigation\ValueObject;

use App\Foundation\Navigation\Entity\Area;
use App\Foundation\Navigation\Entity\Destination;
use App\Foundation\Navigation\Entity\HiddenDestination;
use App\Foundation\Navigation\Entity\Section;

/**
 * Every destination the application has, as pure data.
 *
 * Immutable by construction: it takes finished arrays and exposes no mutating method, so there is
 * no half-built state for a reader to observe. It prunes nothing, authorizes nothing, resolves no
 * URLs and has never heard of a request.
 */
final class Sitemap
{
    /**
     * Derived here rather than passed in, so the lookup cannot drift from the arrays it indexes.
     *
     * @var array<string, Area|Destination|HiddenDestination|Section>
     */
    private readonly array $index;

    /**
     * @param  array<int, Area>  $areas
     * @param  array<int, Section>  $sections
     * @param  array<int, Destination>  $destinations
     * @param  array<int, HiddenDestination>  $hidden  kept apart from the destinations rather than
     *                                                 mixed in, so reading the sitemap for what to
     *                                                 draw cannot pick one up by accident
     */
    public function __construct(
        private readonly array $areas = [],
        private readonly array $sections = [],
        private readonly array $destinations = [],
        private readonly array $hidden = [],
    ) {
        $index = [];

        // First declaration of a key wins. A later duplicate is still returned by the ordered
        // accessors rather than dropped, so it stays visible to anything inspecting the sitemap.
        foreach ([$areas, $sections, $destinations, $hidden] as $group) {
            foreach ($group as $declaration) {
                $index[$declaration->key] ??= $declaration;
            }
        }

        $this->index = $index;
    }

    /**
     * @return array<int, Area>
     */
    public function areas(): array
    {
        return $this->areas;
    }

    /**
     * @return array<int, Section>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * @return array<int, Destination>
     */
    public function destinations(): array
    {
        return $this->destinations;
    }

    /**
     * @return array<int, HiddenDestination>
     */
    public function hidden(): array
    {
        return $this->hidden;
    }

    public function find(string $key): Area|Destination|HiddenDestination|Section|null
    {
        return $this->index[$key] ?? null;
    }
}
