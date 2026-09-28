<?php

namespace App\Foundation\Navigation\Entity;

use App\Foundation\Navigation\Contract\ConditionContract;
use App\Foundation\Navigation\Contract\LabelContract;
use App\Foundation\Navigation\Contract\TargetContract;

/**
 * A place a user can go that no menu lists — an edit screen, a drill-down, a mode of a page reached
 * by acting on a record rather than by clicking an entry.
 *
 * It carries only what answers "where am I": a key, a label, an address, and the ancestry that
 * label hangs off. Nothing about how it looks, because it is never drawn — which is why this is a
 * type of its own rather than a flag switching off two thirds of a bigger one.
 *
 * The target is required rather than nullable. One of these is recognised by its address and by
 * nothing else, so one without an address could never be recognised at all.
 */
final class HiddenDestination
{
    /**
     * @param  string  $parentKey  the navigational parent — an area, a destination, or another
     *                             hidden destination when one mode opens out of another
     * @param  class-string<ConditionContract>|null  $condition
     */
    public function __construct(
        public readonly string $key,
        public readonly LabelContract $label,
        public readonly string $parentKey,
        public readonly TargetContract $target,
        public readonly ?string $permission = null,
        public readonly ?string $condition = null,
    ) {}
}
