<?php

namespace App\Foundation\Navigation\Entity;

use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Navigation\Contract\Label;

/**
 * A presentational bucket within an area — "Transactions", "Inquiries and Reports".
 *
 * Not part of the navigational hierarchy: it groups siblings for display and offers a named slot
 * to declare into, but it never becomes anything's parent.
 */
final class Section
{
    /**
     * @param  string  $parentKey  the area (or destination) whose children this groups
     * @param  class-string<Condition>|null  $condition
     */
    public function __construct(
        public readonly string $key,
        public readonly Label $label,
        public readonly string $parentKey,
        public readonly int $sort = 0,
        public readonly ?string $condition = null,
        public readonly int $order = 0,
    ) {}
}
