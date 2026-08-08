<?php

namespace App\Foundation\Navigation\Service;

use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Navigation\Entity\Area;
use App\Foundation\Navigation\Entity\Destination;
use App\Foundation\Navigation\Entity\HiddenDestination;
use App\Foundation\Navigation\Entity\Node;
use App\Foundation\Navigation\Entity\Section;
use App\Foundation\Navigation\ValueObject\NavigationTree;
use App\Foundation\Navigation\ValueObject\SectionGroup;
use App\Foundation\Navigation\ValueObject\Sitemap;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Turns declarations into the tree one user sees on one request.
 *
 * A pure pipeline over data the sitemap hands it — no request, no response, no view. Because
 * declarations are readonly and nodes are built fresh, there is nothing to reset between runs
 * and two resolutions in one process cannot interfere.
 *
 *   conditions → prune → sort → materialize
 *
 * Every step up to the last works on declarations alone. Nodes are built only once the shape of
 * the tree is settled, bottom-up, which is what lets a node be finished the moment it exists: no
 * half-assembled node is ever reachable, and the mutation needed to grow a tree stays inside this
 * class rather than sitting on the type handed to consumers.
 *
 * What comes out is strictly what this user can reach. Nothing inaccessible survives, so no
 * consumer has to ask.
 *
 * Places no menu lists never enter the pruning pass at all. Sorting, grouping and the rule that
 * empties a branch all exist to settle how a thing is drawn among its siblings, and there is
 * nothing to settle for something that is not drawn. They are gated one by one, then hung off the
 * node they name — which is what makes it impossible for one to influence what a menu shows.
 *
 * When that node was not drawn, they stand on their own rather than going with it. A menu finding
 * it has nothing left to show is a verdict about drawing, and something never drawn cannot be
 * bound by it — a request still lands on such a place, and still has to be told where it is. So
 * what comes back holds two things of different sizes: the areas a menu draws, and an index of
 * everywhere this user can be.
 *
 * Structural soundness of the sitemap is deliberately not checked here — that verdict depends only
 * on inputs fixed at boot, which makes it a build-time question rather than a per-request one. A
 * declaration naming a parent nobody declared costs only itself: nothing lists it as a child, so
 * no area root reaches it and it never enters the tree.
 *
 * Both walks below — up through ancestry, then down through children — assume the parent relation
 * has no loops in it, and neither carries a visited set to fall back on. A sitemap that loops
 * does not make them wrong, it makes them never return.
 */
class Resolver
{
    public function __construct(
        private readonly Sitemap $sitemap,
        private readonly Gate $gate,
    ) {}

    public function resolve(?Authenticatable $user = null): NavigationTree
    {
        // Bound once: forUser() clones the gate, and pruning asks it per node.
        $gate = $this->gate->forUser($user);

        $dropped = $this->dropped();
        $children = $this->childIndex($dropped);
        $sections = $this->sectionIndex($dropped);
        $hidden = $this->hiddenIndex($dropped, $gate);

        $branches = [];

        foreach ($this->sitemap->areas() as $area) {
            if (isset($dropped[$area->key])) {
                continue;
            }

            $branch = $this->prune($area, $children, $gate);

            if ($branch !== null) {
                $branches[] = $branch;
            }
        }

        usort($branches, fn (array $a, array $b) => $this->byRank($a['declaration'], $b['declaration']));

        $areas = new Collection(array_map(
            fn (array $branch) => $this->materialize($branch, $sections, $hidden),
            $branches,
        ));

        $index = $this->index($areas);

        // Union rather than a merge: the left operand keeps its keys, so anything already built
        // stays the node handed out for that key.
        return new NavigationTree($areas, $index + $this->index($this->stranded($hidden, $index)));
    }

    /**
     * Anything a condition switched off, plus everything whose ancestry hits one. A section
     * failing its condition takes its entries with it — the group was declared as a unit.
     *
     * @return array<string, true>
     */
    private function dropped(): array
    {
        $dropped = [];
        $cache = [];

        $groups = [
            $this->sitemap->areas(),
            $this->sitemap->sections(),
            $this->sitemap->destinations(),
            $this->sitemap->hidden(),
        ];

        foreach ($groups as $group) {
            foreach ($group as $declaration) {
                if ($declaration->condition !== null && ! $this->passes($declaration->condition, $cache)) {
                    $dropped[$declaration->key] = true;
                }
            }
        }

        foreach ([...$this->sitemap->destinations(), ...$this->sitemap->hidden()] as $destination) {
            if (isset($dropped[$destination->key])) {
                continue;
            }

            if ($destination instanceof Destination
                && $destination->sectionKey !== null
                && isset($dropped[$destination->sectionKey])) {
                $dropped[$destination->key] = true;

                continue;
            }

            $ancestor = $this->sitemap->find($destination->parentKey);

            while ($ancestor !== null) {
                if (isset($dropped[$ancestor->key])) {
                    $dropped[$destination->key] = true;

                    break;
                }

                $ancestor = $ancestor instanceof Destination || $ancestor instanceof HiddenDestination
                    ? $this->sitemap->find($ancestor->parentKey)
                    : null;
            }
        }

        return $dropped;
    }

    /**
     * @param  array<class-string<Condition>, bool>  $cache
     */
    private function passes(string $condition, array &$cache): bool
    {
        return $cache[$condition] ??= (bool) app($condition)();
    }

    /**
     * Surviving destinations gathered under the key each names as its parent.
     *
     * @param  array<string, true>  $dropped
     * @return array<string, array<int, Destination>>
     */
    private function childIndex(array $dropped): array
    {
        $children = [];

        foreach ($this->sitemap->destinations() as $destination) {
            if (! isset($dropped[$destination->key])) {
                $children[$destination->parentKey][] = $destination;
            }
        }

        return $children;
    }

    /**
     * Surviving hidden destinations gathered under the key each names as its parent.
     *
     * The gate is asked here rather than in the pruning pass because none of that pass applies: a
     * closed gate removes exactly this one entry, and there is no branch to empty and no sibling
     * order to disturb. Each answers for itself, so one whose parent was refused is kept unless
     * its own permission refuses it too.
     *
     * @param  array<string, true>  $dropped
     * @return array<string, array<int, HiddenDestination>>
     */
    private function hiddenIndex(array $dropped, Gate $gate): array
    {
        $hidden = [];

        foreach ($this->sitemap->hidden() as $destination) {
            if (isset($dropped[$destination->key])) {
                continue;
            }

            if ($destination->permission !== null && ! $gate->allows($destination->permission)) {
                continue;
            }

            $hidden[$destination->parentKey][] = $destination;
        }

        return $hidden;
    }

    /**
     * The subtree that survives under this declaration, or null when nothing does. Post-order, so
     * a branch emptied by its children going also goes.
     *
     * Three rules:
     *
     *  - A closed gate takes the whole subtree. No need to look at children at all, since they
     *    are unreachable through a parent you cannot open.
     *  - A leaf survives if it goes somewhere. One with no target leads nowhere by definition.
     *  - A branch survives only if something under it survived. Its own target is not enough — a
     *    branch whose every entry is gated off leads to a page listing nothing.
     *
     * Declared children are read before any of them is pruned, which is what distinguishes a
     * genuine leaf from a branch that just lost everything.
     *
     * @param  array<string, array<int, Destination>>  $children
     * @return array{declaration: Area|Destination, children: array<int, array>}|null
     */
    private function prune(Area|Destination $declaration, array $children, Gate $gate): ?array
    {
        if ($declaration->permission !== null && ! $gate->allows($declaration->permission)) {
            return null;
        }

        $declared = $children[$declaration->key] ?? [];

        if ($declared === []) {
            return $declaration->target === null
                ? null
                : ['declaration' => $declaration, 'children' => []];
        }

        $kept = [];

        foreach ($declared as $child) {
            $branch = $this->prune($child, $children, $gate);

            if ($branch !== null) {
                $kept[] = $branch;
            }
        }

        if ($kept === []) {
            return null;
        }

        usort($kept, fn (array $a, array $b) => $this->byRank($a['declaration'], $b['declaration']));

        return ['declaration' => $declaration, 'children' => $kept];
    }

    /**
     * @param  array{declaration: Area|Destination, children: array<int, array>}  $branch
     * @param  array<string, Section>  $sections
     * @param  array<string, array<int, HiddenDestination>>  $hidden
     */
    private function materialize(array $branch, array $sections, array $hidden): Node
    {
        $children = array_map(
            fn (array $child) => $this->materialize($child, $sections, $hidden),
            $branch['children'],
        );

        return new Node(
            $branch['declaration'],
            $children,
            $this->group($children, $sections),
            $this->materializeHidden($branch['declaration']->key, $hidden),
        );
    }

    /**
     * Built in declaration order and grouped into nothing, because neither is observable on
     * something that is never laid out.
     *
     * @param  array<string, array<int, HiddenDestination>>  $hidden
     * @return array<int, Node>
     */
    private function materializeHidden(string $parentKey, array $hidden): array
    {
        return array_map(
            fn (HiddenDestination $destination) => new Node(
                $destination,
                hiddenChildren: $this->materializeHidden($destination->key, $hidden),
            ),
            $hidden[$parentKey] ?? [],
        );
    }

    /**
     * The survivors whose parent was never built, as roots of their own.
     *
     * A group is a root only when nothing else will carry it. A parent that was drawn already
     * carries its hidden children, and one that is itself a survivor carries them once its own
     * root is built — so skipping both is what keeps a node from being built twice, and what
     * leaves a chain of them hanging together off whichever of them was stranded.
     *
     * @param  array<string, array<int, HiddenDestination>>  $hidden
     * @param  array<string, Node>  $index  everywhere built so far
     * @return Collection<int, Node>
     */
    private function stranded(array $hidden, array $index): Collection
    {
        $survivors = [];

        foreach ($hidden as $group) {
            foreach ($group as $destination) {
                $survivors[$destination->key] = true;
            }
        }

        $roots = [];

        foreach (array_keys($hidden) as $parentKey) {
            if (isset($index[$parentKey]) || isset($survivors[$parentKey])) {
                continue;
            }

            $roots = [...$roots, ...$this->materializeHidden($parentKey, $hidden)];
        }

        return new Collection($roots);
    }

    /**
     * Bundles children into the sections that group them. A section with nothing left in it is
     * dropped rather than kept empty.
     *
     * @param  array<int, Node>  $children
     * @param  array<string, Section>  $sections
     * @return array<int, SectionGroup>
     */
    private function group(array $children, array $sections): array
    {
        $grouped = [];

        foreach ($children as $child) {
            $key = $child->declaration instanceof Destination ? $child->declaration->sectionKey : null;

            if ($key !== null && isset($sections[$key])) {
                $grouped[$key][] = $child;
            }
        }

        $groups = [];

        foreach ($grouped as $key => $items) {
            $groups[] = new SectionGroup($sections[$key], new Collection($items));
        }

        usort($groups, fn (SectionGroup $a, SectionGroup $b) => [$a->section->sort, $a->section->order]
            <=> [$b->section->sort, $b->section->order]);

        return $groups;
    }

    /**
     * Declared sort first, declaration order as the tiebreaker.
     */
    private function byRank(Area|Destination $a, Area|Destination $b): int
    {
        return [$a->sort, $a->order] <=> [$b->sort, $b->order];
    }

    /**
     * @param  array<string, true>  $dropped
     * @return array<string, Section>
     */
    private function sectionIndex(array $dropped): array
    {
        $sections = [];

        foreach ($this->sitemap->sections() as $section) {
            if (! isset($dropped[$section->key])) {
                $sections[$section->key] ??= $section;
            }
        }

        return $sections;
    }

    /**
     * Pre-order, so a parent is indexed before anything under it.
     *
     * Hidden nodes are indexed alongside the rest: they are left out of menus, not out of the tree,
     * and a lookup by key has to find one for a trail to be walked up from it.
     *
     * @param  Collection<int, Node>  $roots
     * @return array<string, Node>
     */
    private function index(Collection $roots): array
    {
        $index = [];

        $walk = function (Node $node) use (&$walk, &$index): void {
            $index[$node->key()] ??= $node;

            foreach ([...$node->children(), ...$node->hiddenChildren()] as $child) {
                $walk($child);
            }
        };

        foreach ($roots as $root) {
            $walk($root);
        }

        return $index;
    }
}
