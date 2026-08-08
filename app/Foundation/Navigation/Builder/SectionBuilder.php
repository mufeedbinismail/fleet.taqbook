<?php

namespace App\Foundation\Navigation\Builder;

use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Navigation\Contract\Label;
use App\Foundation\Navigation\Entity\Section;

final class SectionBuilder
{
    private int $sort = 0;

    private ?string $condition = null;

    /**
     * @param  string  $prefix  key namespace for anything declared inside this section
     */
    public function __construct(
        private readonly Builder $root,
        private readonly string $key,
        private readonly Label $label,
        private readonly string $parentKey,
        private readonly string $prefix,
        private readonly int $order,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

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
     * Declare a destination inside this section. The key is namespaced to the owning area rather
     * than to the section, so an entry keeps its key when it moves between sections.
     */
    public function page(string $key, string|Label $label): DestinationBuilder
    {
        return $this->root->page($key, $label, into: $this->key)->prefix($this->prefix);
    }

    /**
     * A place no menu lists, keyed and parented as though it were declared here. It joins no
     * grouping — there is nothing to group when there is nothing to draw.
     */
    public function hiddenPage(string $key, string|Label $label): HiddenDestinationBuilder
    {
        return $this->root->hiddenPage($key, $label, into: $this->key)->prefix($this->prefix);
    }

    public function build(): Section
    {
        return new Section(
            key: $this->key,
            label: $this->label,
            parentKey: $this->parentKey,
            sort: $this->sort,
            condition: $this->condition,
            order: $this->order,
        );
    }
}
