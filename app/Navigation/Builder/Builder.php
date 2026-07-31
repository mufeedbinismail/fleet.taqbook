<?php

namespace App\Navigation\Builder;

use App\Navigation\Contract\Label;
use App\Navigation\Contract\NavigationSource;
use App\Navigation\Entity\Area;
use App\Navigation\Entity\Destination;
use App\Navigation\Entity\HiddenDestination;
use App\Navigation\Entity\Section;
use App\Navigation\Exception\NavigationException;
use App\Navigation\ValueObject\Sitemap;
use App\Navigation\ValueObject\TranslatedLabel;
use Closure;

/**
 * The declaration DSL. Pure translation — fluent calls in, value objects out. It resolves no
 * URLs, checks no permissions, and has never heard of a request.
 *
 * Key namespacing is carried by the sub-builder that owns it rather than by cursor state here, so
 * a builder kept past its closure still namespaces its children correctly.
 *
 * Materialization is private and reachable only through buildFromSources. That is deliberate:
 * slots resolve against the finished section list, so a caller able to materialize early would
 * see a sitemap missing every declaration made after its own.
 */
final class Builder
{
    /** @var array<int, AreaBuilder> */
    private array $areas = [];

    /** @var array<int, SectionBuilder> */
    private array $sections = [];

    /** @var array<int, DestinationBuilder> */
    private array $destinations = [];

    /** @var array<int, HiddenDestinationBuilder> */
    private array $hidden = [];

    private int $order = 0;

    /**
     * @param  iterable<NavigationSource>  $sources
     */
    public static function buildFromSources(iterable $sources): Sitemap
    {
        $builder = new self;

        foreach ($sources as $source) {
            $source->declare($builder);
        }

        return $builder->assemble();
    }

    /**
     * @param  Closure(AreaBuilder): void|null  $define
     */
    public function area(string $key, string|Label $label, ?Closure $define = null): AreaBuilder
    {
        $area = new AreaBuilder($this, $key, self::label($label), $this->order++);

        $this->areas[] = $area;

        if ($define !== null) {
            $define($area);
        }

        return $area;
    }

    /**
     * @internal
     *
     * @param  string  $key  the section's own key, taken verbatim — a section is not a child of
     *                       the area that declares it, so it does not inherit the area's key
     * @param  string  $prefix  key namespace for anything declared inside this section
     * @param  Closure(SectionBuilder): void|null  $define
     */
    public function section(string $key, string|Label $label, string $parentKey, string $prefix, ?Closure $define = null): SectionBuilder
    {
        $section = new SectionBuilder($this, $key, self::label($label), $parentKey, $prefix, $this->order++);

        $this->sections[] = $section;

        if ($define !== null) {
            $define($section);
        }

        return $section;
    }

    /**
     * Declare a destination into a slot another domain owns — an area, a section, or another
     * destination. The key is taken verbatim, because a contributed entry belongs to the domain
     * declaring it rather than to the area hosting it, and must keep that key if it later moves.
     *
     *     $nav->page('finance.tax-rate.manage', $label, Section::SYSTEM_COMPANY)
     *         ->target(...)
     *         ->permission(Permission::MANAGE_TAX_RATE);
     *
     * @param  string  $into  the slot to hang this in; resolved once every source has declared
     */
    public function page(string $key, string|Label $label, string $into): DestinationBuilder
    {
        return $this->push($key, $label, $into);
    }

    /**
     * Declare a place no menu lists into a slot another domain owns. Keys work as they do for a
     * listed entry, for the same reason: an entry that later becomes reachable from a menu, or
     * moves to the domain that owns its page, must not change name doing it.
     *
     * @param  string  $into  the slot to hang this in; resolved once every source has declared
     */
    public function hiddenPage(string $key, string|Label $label, string $into): HiddenDestinationBuilder
    {
        return $this->pushHidden($key, $label, $into);
    }

    private function push(string $key, string|Label $label, string $into): DestinationBuilder
    {
        $destination = new DestinationBuilder($key, self::label($label), $into, $this->order++);

        $this->destinations[] = $destination;

        return $destination;
    }

    /**
     * No order is taken. Order is a tiebreaker between siblings competing for a position, and
     * these are never placed anywhere.
     */
    private function pushHidden(string $key, string|Label $label, string $into): HiddenDestinationBuilder
    {
        $destination = new HiddenDestinationBuilder($key, self::label($label), $into);

        $this->hidden[] = $destination;

        return $destination;
    }

    /**
     * A bare string is a translation key, never rendered text: resolving it here would bake one
     * locale into a declaration that is read by every request.
     */
    private static function label(string|Label $label): Label
    {
        return $label instanceof Label ? $label : TranslatedLabel::of($label);
    }

    /**
     * Destinations materialize last because they resolve their slot against the finished section
     * list, keyed with the first declaration of a key winning.
     */
    private function assemble(): Sitemap
    {
        $areas = $this->buildAreas();
        $sections = $this->buildSections();

        $keyed = [];

        foreach ($sections as $section) {
            $keyed[$section->key] ??= $section;
        }

        $destinations = $this->buildDestinations($keyed);
        $hidden = $this->buildHidden($keyed);

        $this->guardKeys($areas, $sections, $destinations, $hidden);
        $this->guardCycles([...$destinations, ...$hidden]);

        return new Sitemap($areas, $sections, $destinations, $hidden);
    }

    /**
     * A key names one slot, and the finished sitemap is read by key: a lookup answers "what hangs
     * under this name". Two declarations claiming one name make that question ambiguous — the
     * same subtree would answer for both, and which of the two a lookup returns comes down to
     * declaration order.
     *
     * Refused outright rather than reported, because there is no honest way to pick a winner, and
     * because refusing while both offenders are still in hand names them all in one go.
     *
     * Two entries pointing at the same *URL* are untouched by this: distinct keys are exactly how
     * one page is reached from two places.
     *
     * @param  array<int, Area|Section|Destination|HiddenDestination>  ...$groups
     */
    private function guardKeys(array ...$groups): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($groups as $group) {
            foreach ($group as $declaration) {
                if (isset($seen[$declaration->key])) {
                    $duplicates[$declaration->key] = true;

                    continue;
                }

                $seen[$declaration->key] = true;
            }
        }

        if ($duplicates !== []) {
            throw NavigationException::duplicateKeys(array_keys($duplicates));
        }
    }

    /**
     * Unique keys are not enough to rule this out: two entries naming each other as parent are
     * two perfectly ordinary declarations, and a chain that comes back to where it started has no
     * end to walk to. Reading such a sitemap does not go wrong, it goes on — which is why this is
     * settled once here rather than defended against by every walk over the result.
     *
     * Only destinations carry a parent that can lead anywhere: an area has none, and a section has
     * already been resolved to the slot behind it by the time this runs.
     *
     * @param  array<int, Destination|HiddenDestination>  $destinations
     */
    private function guardCycles(array $destinations): void
    {
        $parents = [];

        foreach ($destinations as $destination) {
            $parents[$destination->key] = $destination->parentKey;
        }

        $cycles = [];

        foreach ($destinations as $destination) {
            $seen = [];

            for ($key = $destination->key; isset($parents[$key]); $key = $parents[$key]) {
                if (isset($seen[$key])) {
                    $cycles[$key] = true;

                    break;
                }

                $seen[$key] = true;
            }
        }

        if ($cycles !== []) {
            throw NavigationException::cycles(array_keys($cycles));
        }
    }

    /**
     * @return array<int, Area>
     */
    private function buildAreas(): array
    {
        return array_map(fn (AreaBuilder $area) => $area->build(), $this->areas);
    }

    /**
     * @return array<int, Section>
     */
    private function buildSections(): array
    {
        return array_map(fn (SectionBuilder $section) => $section->build(), $this->sections);
    }

    /**
     * Slots are resolved here rather than at declaration time: a destination may be declared into
     * a section that has not been defined yet.
     *
     * @param  array<string, Section>  $sections
     * @return array<int, Destination>
     */
    private function buildDestinations(array $sections): array
    {
        return array_map(fn (DestinationBuilder $item) => $item->build($sections), $this->destinations);
    }

    /**
     * @param  array<string, Section>  $sections
     * @return array<int, HiddenDestination>
     */
    private function buildHidden(array $sections): array
    {
        return array_map(fn (HiddenDestinationBuilder $item) => $item->build($sections), $this->hidden);
    }
}
