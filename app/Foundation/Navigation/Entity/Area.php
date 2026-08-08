<?php

namespace App\Foundation\Navigation\Entity;

use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Navigation\Contract\Label;
use App\Foundation\Navigation\Contract\Target;

/**
 * A top-level tree — Sales, Finance, Setup.
 *
 * Distinct from Destination rather than "a destination with no parent" because it has no parent
 * key at all, and because it carries top-level concerns (icon, help context) a leaf has no use for.
 */
final class Area
{
    /**
     * @param  class-string<Condition>|null  $condition
     * @param  int  $order  declaration order, the tiebreaker when two areas share a sort
     */
    public function __construct(
        public readonly string $key,
        public readonly Label $label,
        public readonly ?Target $target = null,
        public readonly ?string $permission = null,
        public readonly ?string $icon = null,
        public readonly ?string $help = null,
        public readonly int $sort = 0,
        public readonly ?string $condition = null,
        public readonly int $order = 0,
    ) {}
}
