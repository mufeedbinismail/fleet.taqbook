<?php

namespace App\Foundation\Navigation\Builder;

use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Navigation\Contract\Label;
use App\Foundation\Navigation\Contract\Target;
use App\Foundation\Navigation\Entity\Destination;
use App\Foundation\Navigation\Entity\Section;
use App\Foundation\Navigation\Enum\Category;
use App\Foundation\Navigation\Enum\Column;
use App\Foundation\Navigation\ValueObject\Placement;

/**
 * Draft of a single destination, mutable until it is materialized into a readonly Destination.
 */
final class DestinationBuilder
{
    private ?Target $target = null;

    private ?string $permission = null;

    private ?Category $category = null;

    private ?string $icon = null;

    private ?string $help = null;

    private int $sort = 0;

    private ?string $condition = null;

    private ?string $prefix = null;

    private Placement $placement;

    /**
     * @param  string  $slot  the area, section or destination this was declared inside; which of
     *                        those it turns out to be is resolved at materialization
     */
    public function __construct(
        private readonly string $key,
        private readonly Label $label,
        private string $slot,
        private readonly int $order,
    ) {
        $this->placement = new Placement;
    }

    public function key(): string
    {
        return $this->prefix === null ? $this->key : "{$this->prefix}.{$this->key}";
    }

    public function prefix(string $key): self
    {
        $this->prefix = $key;

        return $this;
    }

    public function target(Target $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function permission(?string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function category(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function sort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Takes the parts rather than a built Placement, so the common call reads as one line and no
     * source has to import the value object to state where an entry goes.
     */
    public function place(Column $column, int $cluster = 0): self
    {
        $this->placement = new Placement($column, $cluster);

        return $this;
    }

    /**
     * @param  class-string<Condition>  $condition
     */
    public function when(string $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Nest under another destination rather than directly under the area.
     */
    public function under(string $destinationKey): self
    {
        $this->slot = $destinationKey;

        return $this;
    }

    /**
     * @param  array<string, Section>  $sections
     */
    public function build(array $sections): Destination
    {
        $section = $sections[$this->slot] ?? null;

        return new Destination(
            key: $this->key(),
            label: $this->label,
            parentKey: $section?->parentKey ?? $this->slot,
            sectionKey: $section?->key,
            target: $this->target,
            permission: $this->permission,
            category: $this->category,
            icon: $this->icon,
            help: $this->help,
            sort: $this->sort,
            condition: $this->condition,
            order: $this->order,
            placement: $this->placement,
        );
    }
}
