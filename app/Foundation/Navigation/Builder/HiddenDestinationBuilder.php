<?php

namespace App\Foundation\Navigation\Builder;

use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Navigation\Contract\Label;
use App\Foundation\Navigation\Contract\Target;
use App\Foundation\Navigation\Entity\HiddenDestination;
use App\Foundation\Navigation\Entity\Section;
use App\Foundation\Navigation\Exception\NavigationException;

/**
 * Draft of a single hidden destination, mutable until it is materialized into a readonly one.
 *
 * Offers no sort, no category and no icon: there is no order to be in and nothing to draw.
 */
final class HiddenDestinationBuilder
{
    private ?Target $target = null;

    private ?string $permission = null;

    private ?string $condition = null;

    private ?string $prefix = null;

    /**
     * @param  string  $slot  the area, section or destination this was declared inside; which of
     *                        those it turns out to be is resolved at materialization
     */
    public function __construct(
        private readonly string $key,
        private readonly Label $label,
        private string $slot,
    ) {}

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

    /**
     * @param  class-string<Condition>  $condition
     */
    public function when(string $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Hang this beneath a destination rather than directly under the area, which is what puts the
     * entry it opens out of into the trail ahead of it.
     */
    public function under(string $destinationKey): self
    {
        $this->slot = $destinationKey;

        return $this;
    }

    /**
     * Refuses an address-less declaration outright rather than reporting it, because there is
     * nothing left to build: the value type has no room for the absence.
     *
     * A section slot resolves to the section's own parent. Sections group entries for display and
     * this is never displayed, so being declared inside one says where it belongs and nothing more.
     *
     * @param  array<string, Section>  $sections
     */
    public function build(array $sections): HiddenDestination
    {
        if ($this->target === null) {
            throw NavigationException::unaddressed($this->key());
        }

        $section = $sections[$this->slot] ?? null;

        return new HiddenDestination(
            key: $this->key(),
            label: $this->label,
            parentKey: $section?->parentKey ?? $this->slot,
            target: $this->target,
            permission: $this->permission,
            condition: $this->condition,
        );
    }
}
