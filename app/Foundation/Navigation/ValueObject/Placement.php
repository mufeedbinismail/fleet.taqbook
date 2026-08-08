<?php

namespace App\Foundation\Navigation\ValueObject;

use App\Foundation\Navigation\Enum\Column;

/**
 * Where a destination sits within its section, for a view that lays a section out in two dimensions.
 *
 * Purely presentational, and deliberately not an ordering: the cluster says which run of entries
 * this one belongs with, never which comes first. Order among siblings stays settled by rank alone,
 * so a view that reads placement and a view that ignores it agree about everything except shape.
 *
 * The cluster is affinity — "these entries belong together" — rather than a request for a gap. A
 * gap is what a view chooses to draw at a boundary between two clusters that both have something in
 * them, which is why a cluster whose every entry was pruned cannot leave one behind.
 */
final class Placement
{
    public function __construct(
        public readonly Column $column = Column::Left,
        public readonly int $cluster = 0,
    ) {}
}
