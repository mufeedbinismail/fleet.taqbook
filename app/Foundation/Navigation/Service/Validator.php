<?php

namespace App\Foundation\Navigation\Service;

use App\Foundation\Auth\Model\Permission;
use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Navigation\DTO\Problem;
use App\Foundation\Navigation\DTO\Report;
use App\Foundation\Navigation\Entity\Area;
use App\Foundation\Navigation\Entity\Destination;
use App\Foundation\Navigation\Entity\HiddenDestination;
use App\Foundation\Navigation\Entity\Section;
use App\Foundation\Navigation\ValueObject\Sitemap;
use Illuminate\Contracts\Auth\Access\Gate;
use Throwable;

/**
 * Inspects a sitemap and reports everything wrong with it in one pass.
 *
 * Validation is a pass, not a scattering of throws: a source that names a parent nobody declared
 * should cost that subtree, not every page in the application. What to do about the report is the
 * caller's decision — this class only ever reports.
 */
class Validator
{
    public function __construct(private readonly Gate $gate) {}

    public function inspect(Sitemap $sitemap): Report
    {
        $problems = [];
        $fatal = [];

        $areas = $sitemap->areas();
        $sections = $sitemap->sections();
        $destinations = $sitemap->destinations();
        $hidden = $sitemap->hidden();

        // Hidden entries are held apart in the sitemap but checked with everything else. They share
        // the key namespace, the parent chain and — the one that bites — the set of addresses, so
        // an unlisted mode colliding with a listed page is exactly the collision worth catching.
        $places = [...$destinations, ...$hidden];

        $this->duplicates([...$areas, ...$sections, ...$places], $problems);
        $this->parents($sitemap, $sections, $places, $problems, $fatal);
        $this->cycles($sitemap, $places, $problems, $fatal);
        $this->areaSorts($areas, $problems);
        $this->conditions([...$areas, ...$sections, ...$places], $problems, $fatal);
        $this->targets([...$areas, ...$places], $problems);
        $this->permissions([...$areas, ...$places], $problems);

        return new Report($problems, $fatal);
    }

    /**
     * Fatal, yet nothing is added to the drop list: which of the two declarations is the mistake
     * is not knowable from here, and dropping the later one would leave the tree quietly missing
     * an entry someone declared on purpose. Sections and destinations share one namespace
     * deliberately — a slot is a slot, whichever kind it is — so a collision has to be answered by
     * whoever declared it rather than resolved by guessing.
     *
     * @param  array<int, Area|Destination|HiddenDestination|Section>  $declarations
     * @param  array<int, Problem>  $problems
     */
    private function duplicates(array $declarations, array &$problems): void
    {
        $seen = [];

        foreach ($declarations as $declaration) {
            if (isset($seen[$declaration->key])) {
                $problems[] = Problem::duplicateKey($declaration->key);

                continue;
            }

            $seen[$declaration->key] = true;
        }
    }

    /**
     * @param  array<int, Section>  $sections
     * @param  array<int, Destination|HiddenDestination>  $destinations
     * @param  array<int, Problem>  $problems
     * @param  array<string, true>  $fatal
     */
    private function parents(Sitemap $sitemap, array $sections, array $destinations, array &$problems, array &$fatal): void
    {
        foreach ($sections as $section) {
            $parent = $sitemap->find($section->parentKey);

            if ($parent === null) {
                $problems[] = new Problem(
                    Problem::MISSING_PARENT,
                    $section->key,
                    "Groups entries under [{$section->parentKey}], which nothing declares.",
                );

                $fatal[$section->key] = true;
            }
        }

        foreach ($destinations as $destination) {
            $parent = $sitemap->find($destination->parentKey);

            if ($parent === null) {
                $problems[] = new Problem(
                    Problem::MISSING_PARENT,
                    $destination->key,
                    "Names parent [{$destination->parentKey}], which nothing declares. Contributing "
                    .'into another domain requires that domain to have declared the slot first.',
                );

                $fatal[$destination->key] = true;

                continue;
            }

            if ($parent instanceof Section) {
                $problems[] = new Problem(
                    Problem::SECTION_MISPLACED,
                    $destination->key,
                    "Resolved its parent to section [{$parent->key}]. Sections group, they do not "
                    .'parent — this indicates a bug in slot resolution.',
                );

                $fatal[$destination->key] = true;
            }

            // A hidden entry hanging off another is how one mode opens out of another, and the
            // trail reads correctly. A listed one hanging off a hidden entry does not: the parent
            // appears in no menu, so no menu can offer a way down to the child.
            if ($parent instanceof HiddenDestination && ! $destination instanceof HiddenDestination) {
                $problems[] = new Problem(
                    Problem::HIDDEN_PARENT,
                    $destination->key,
                    "Hangs under [{$parent->key}], which no menu lists. Nothing would ever draw a "
                    .'way to reach this entry.',
                );

                $fatal[$destination->key] = true;
            }

            if ($destination instanceof Destination
                && $destination->sectionKey !== null
                && ! $sitemap->find($destination->sectionKey) instanceof Section) {
                $problems[] = new Problem(
                    Problem::MISSING_SECTION,
                    $destination->key,
                    "Declared into section [{$destination->sectionKey}], which is not a section.",
                );
            }
        }
    }

    /**
     * @param  array<int, Destination|HiddenDestination>  $destinations
     * @param  array<int, Problem>  $problems
     * @param  array<string, true>  $fatal
     */
    private function cycles(Sitemap $sitemap, array $destinations, array &$problems, array &$fatal): void
    {
        foreach ($destinations as $destination) {
            $seen = [$destination->key => true];

            for ($node = $sitemap->find($destination->parentKey); $node instanceof Destination || $node instanceof HiddenDestination;) {
                if (isset($seen[$node->key])) {
                    $problems[] = new Problem(
                        Problem::CYCLE,
                        $destination->key,
                        "Its ancestry loops back through [{$node->key}].",
                    );

                    $fatal[$destination->key] = true;

                    break;
                }

                $seen[$node->key] = true;
                $node = $sitemap->find($node->parentKey);
            }
        }
    }

    /**
     * Two areas sharing a sort fall back to declaration order, which no single source controls.
     * Worth a warning, not a failure.
     *
     * @param  array<int, Area>  $areas
     * @param  array<int, Problem>  $problems
     */
    private function areaSorts(array $areas, array &$problems): void
    {
        $bySort = [];

        foreach ($areas as $area) {
            $bySort[$area->sort][] = $area->key;
        }

        foreach ($bySort as $sort => $keys) {
            if (count($keys) > 1) {
                $problems[] = new Problem(
                    Problem::AMBIGUOUS_AREA_SORT,
                    implode(', ', $keys),
                    "Share sort {$sort}, so sidebar order falls back to provider load order. Give "
                    .'each area an explicit sort.',
                    fatal: false,
                );
            }
        }
    }

    /**
     * @param  array<int, Area|Destination|HiddenDestination|Section>  $declarations
     * @param  array<int, Problem>  $problems
     * @param  array<string, true>  $fatal
     */
    private function conditions(array $declarations, array &$problems, array &$fatal): void
    {
        foreach ($declarations as $declaration) {
            if ($declaration->condition === null) {
                continue;
            }

            if (! is_subclass_of($declaration->condition, Condition::class)) {
                $problems[] = new Problem(
                    Problem::UNKNOWN_CONDITION,
                    $declaration->key,
                    "Gated on [{$declaration->condition}], which does not implement ".Condition::class.'.',
                );

                $fatal[$declaration->key] = true;
            }
        }
    }

    /**
     * @param  array<int, Area|Destination|HiddenDestination>  $declarations
     * @param  array<int, Problem>  $problems
     */
    private function targets(array $declarations, array &$problems): void
    {
        $signatures = [];

        foreach ($declarations as $declaration) {
            if ($declaration->target === null) {
                continue;
            }

            try {
                $declaration->target->url();
            } catch (Throwable $exception) {
                $problems[] = new Problem(
                    Problem::UNRESOLVABLE_TARGET,
                    $declaration->key,
                    'Its target cannot build a URL: '.$exception->getMessage(),
                );

                continue;
            }

            $signature = $declaration->target->signature();

            if (isset($signatures[$signature])) {
                $problems[] = new Problem(
                    Problem::DUPLICATE_TARGET,
                    $declaration->key,
                    "Points at the same place as [{$signatures[$signature]}], so the two cannot be "
                    .'told apart when resolving the current location.',
                    fatal: false,
                );

                continue;
            }

            $signatures[$signature] = $declaration->key;
        }
    }

    /**
     * @param  array<int, Area|Destination|HiddenDestination>  $declarations
     * @param  array<int, Problem>  $problems
     */
    private function permissions(array $declarations, array &$problems): void
    {
        $catalog = Permission::pluck('key')->flip()->all();

        // Nothing granted yet is not the same as an ability being absent. An unseeded catalog
        // switches the check off rather than condemning every entry in the sitemap.
        if ($catalog === []) {
            return;
        }

        foreach ($declarations as $declaration) {
            if ($declaration->permission === null || isset($catalog[$declaration->permission])) {
                continue;
            }

            // Abilities registered in code rather than sourced from the catalog. Legitimate, and
            // absent from it by definition.
            if ($this->gate->has($declaration->permission)) {
                continue;
            }

            $problems[] = new Problem(
                Problem::UNKNOWN_PERMISSION,
                $declaration->key,
                "Requires [{$declaration->permission}], which is not a known ability — the entry "
                .'can never become visible.',
            );
        }
    }
}
