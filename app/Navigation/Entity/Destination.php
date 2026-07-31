<?php

namespace App\Navigation\Entity;

use App\Navigation\Contract\Condition;
use App\Navigation\Contract\Label;
use App\Navigation\Contract\Target;
use App\Navigation\Enum\Category;
use App\Navigation\ValueObject\Placement;

/**
 * A place a user can go.
 *
 * `parentKey` is the navigational parent — an Area, or another Destination when nesting.
 * `sectionKey` is presentational only and is deliberately not part of that chain.
 */
final class Destination
{
    /**
     * @param  class-string<Condition>|null  $condition
     * @param  int  $order  declaration order, the tiebreaker when two siblings share a sort
     * @param  Placement  $placement  defaulted rather than nullable, so what an undeclared
     *                                destination carries is a real answer instead of a missing one
     *                                and no view needs a branch for its absence
     */
    public function __construct(
        public readonly string $key,
        public readonly Label $label,
        public readonly string $parentKey,
        public readonly ?string $sectionKey = null,
        public readonly ?Target $target = null,
        public readonly ?string $permission = null,
        public readonly ?Category $category = null,
        public readonly ?string $icon = null,
        public readonly ?string $help = null,
        public readonly int $sort = 0,
        public readonly ?string $condition = null,
        public readonly int $order = 0,
        public readonly Placement $placement = new Placement,
    ) {}
}
