<?php

namespace App\Foundation\Navigation\Entity;

use App\Foundation\Navigation\Contract\ConditionContract;
use App\Foundation\Navigation\Contract\LabelContract;
use App\Foundation\Navigation\Contract\TargetContract;

/**
 * A top-level tree — Sales, Finance, Setup.
 *
 * Distinct from Destination rather than "a destination with no parent" because it has no parent
 * key at all, and because it carries top-level concerns (icon, help context) a leaf has no use for.
 */
final class Area
{
    /**
     * @param  class-string<ConditionContract>|null  $condition
     * @param  int  $order  declaration order, the tiebreaker when two areas share a sort
     */
    public function __construct(
        public readonly string $key,
        public readonly LabelContract $label,
        public readonly ?TargetContract $target = null,
        public readonly ?string $permission = null,
        public readonly ?string $icon = null,
        public readonly ?string $help = null,
        public readonly int $sort = 0,
        public readonly ?string $condition = null,
        public readonly int $order = 0,
    ) {}
}
