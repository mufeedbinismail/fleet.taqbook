<?php

namespace App\Foundation\Navigation\Entity;

use App\Foundation\Navigation\Contract\Label;
use App\Foundation\Navigation\Enum\Category;
use App\Foundation\Navigation\ValueObject\SectionGroup;
use Illuminate\Support\Collection;
use Throwable;

/**
 * A resolved place in the tree, for one user on one request.
 *
 * Deliberately a different type from the Area/Destination it wraps: declarations are readonly and
 * application-scoped, while nodes are built fresh and thrown away, so nothing here needs resetting
 * between builds and no request can observe another's resolution.
 *
 * A node is finished the moment it exists. Children and their groupings arrive through the
 * constructor and there is no way to change them afterwards, so a node handed out cannot be
 * rearranged behind the back of whoever assembled it.
 *
 * Carries no "active" flag. Which node the current request is on is a property of the request, not
 * of the tree, and keeping it off the node is what stops a stale highlight surviving a re-resolve.
 */
final class Node
{
    /**
     * Written by the parent's constructor, which is the only moment a node learns where it hangs —
     * a parent cannot be passed in, since it does not exist until its children do.
     */
    private ?Node $parent = null;

    private ?string $url = null;

    private bool $urlResolved = false;

    /**
     * @param  array<int, Node>  $children
     * @param  array<int, SectionGroup>  $sections  the same children, bundled by the section that
     *                                              groups them, in section order
     * @param  array<int, Node>  $hiddenChildren  places under this one that no menu lists; they get
     *                                            a parent like anything else, so a trail can be
     *                                            walked up from them
     */
    public function __construct(
        public readonly Area|Destination|HiddenDestination $declaration,
        private readonly array $children = [],
        private readonly array $sections = [],
        private readonly array $hiddenChildren = [],
    ) {
        foreach ([...$children, ...$hiddenChildren] as $child) {
            $child->parent = $this;
        }
    }

    public function key(): string
    {
        return $this->declaration->key;
    }

    public function label(): Label
    {
        return $this->declaration->label;
    }

    public function icon(): ?string
    {
        return $this->declaration instanceof HiddenDestination ? null : $this->declaration->icon;
    }

    public function category(): ?Category
    {
        return $this->declaration instanceof Destination ? $this->declaration->category : null;
    }

    public function permission(): ?string
    {
        return $this->declaration->permission;
    }

    public function isArea(): bool
    {
        return $this->declaration instanceof Area;
    }

    public function parent(): ?Node
    {
        return $this->parent;
    }

    /**
     * This node, or one above it, carries the key. The walk runs upward, so a match means the key
     * names either this place or something this place sits inside.
     */
    public function isWithin(string $key): bool
    {
        for ($node = $this; $node !== null; $node = $node->parent) {
            if ($node->key() === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * The area this node sits in, or null for one hanging off no area at all.
     */
    public function area(): ?Node
    {
        for ($node = $this; $node !== null; $node = $node->parent) {
            if ($node->isArea()) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, Node>
     */
    public function children(): Collection
    {
        return new Collection($this->children);
    }

    /**
     * Separate from children rather than mixed in and filtered back out, so drawing a menu cannot
     * put one on screen by forgetting a rule, and so one of these can never decide whether the
     * entry above it survives.
     *
     * @return Collection<int, Node>
     */
    public function hiddenChildren(): Collection
    {
        return new Collection($this->hiddenChildren);
    }

    /**
     * @return Collection<int, SectionGroup>
     */
    public function sections(): Collection
    {
        return new Collection($this->sections);
    }

    /**
     * Ancestors then self. Sections are absent by construction, since a section is never a parent.
     *
     * @return Collection<int, Node>
     */
    public function trail(): Collection
    {
        $trail = new Collection;

        for ($node = $this; $node !== null; $node = $node->parent) {
            $trail->prepend($node);
        }

        return $trail;
    }

    /**
     * Resolved on demand, once. A target that cannot build its URL yields null rather than
     * throwing, so one bad route name costs one entry instead of the whole tree.
     */
    public function url(): ?string
    {
        if ($this->urlResolved) {
            return $this->url;
        }

        $this->urlResolved = true;

        try {
            $this->url = $this->declaration->target?->url();
        } catch (Throwable) {
            $this->url = null;
        }

        return $this->url;
    }
}
