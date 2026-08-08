<?php

namespace App\Foundation\Navigation\ValueObject;

use App\Foundation\Navigation\Entity\Node;
use App\Foundation\Navigation\Entity\Section;
use Illuminate\Support\Collection;

/**
 * A section paired with the nodes it groups. Never constructed empty: a group with no items left
 * is dropped rather than carried as a bare header.
 */
final class SectionGroup
{
    /**
     * @param  Collection<int, Node>  $items
     */
    public function __construct(
        public readonly Section $section,
        public readonly Collection $items,
    ) {}
}
