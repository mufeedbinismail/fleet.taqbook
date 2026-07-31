<?php

namespace App\Navigation\Builder;

use App\Navigation\Contract\Condition;
use App\Navigation\Contract\Label;
use App\Navigation\Contract\Target;
use App\Navigation\Entity\Area;
use Closure;

final class AreaBuilder
{
    private ?Target $target = null;

    private ?string $permission = null;

    private ?string $icon = null;

    private ?string $help = null;

    private int $sort = 0;

    private ?string $condition = null;

    public function __construct(
        private readonly Builder $root,
        private readonly string $key,
        private readonly Label $label,
        private readonly int $order,
    ) {}

    public function key(): string
    {
        return $this->key;
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

    /**
     * Position among areas. Set it explicitly; areas sharing a sort fall back to declaration
     * order, which is not something a source controls on its own.
     */
    public function sort(int $sort): self
    {
        $this->sort = $sort;

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
     * @param  string  $key  the section's own key — see `App\Navigation\Constant\Section`
     * @param  Closure(SectionBuilder): void|null  $define
     */
    public function section(string $key, string|Label $label, ?Closure $define = null): SectionBuilder
    {
        return $this->root->section($key, $label, parentKey: $this->key, prefix: $this->key, define: $define);
    }

    /**
     * A destination sitting directly in the area, outside any section.
     */
    public function page(string $key, string|Label $label): DestinationBuilder
    {
        return $this->root->page($key, $label, into: $this->key)->prefix($this->key);
    }

    /**
     * A place inside this area that no menu lists.
     */
    public function hiddenPage(string $key, string|Label $label): HiddenDestinationBuilder
    {
        return $this->root->hiddenPage($key, $label, into: $this->key)->prefix($this->key);
    }

    public function build(): Area
    {
        return new Area(
            key: $this->key,
            label: $this->label,
            target: $this->target,
            permission: $this->permission,
            icon: $this->icon,
            help: $this->help,
            sort: $this->sort,
            condition: $this->condition,
            order: $this->order,
        );
    }
}
