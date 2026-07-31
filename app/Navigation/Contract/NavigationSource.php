<?php

namespace App\Navigation\Contract;

use App\Navigation\Builder\Builder;

/**
 * A domain's contribution to the sitemap.
 */
interface NavigationSource
{
    public function declare(Builder $nav): void;
}
