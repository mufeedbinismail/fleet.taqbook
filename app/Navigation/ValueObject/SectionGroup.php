<?php

namespace App\Navigation\ValueObject;

use App\Navigation\Entity\Node;
use App\Navigation\Entity\Section;
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
