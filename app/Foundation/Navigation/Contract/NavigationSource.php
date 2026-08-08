<?php

namespace App\Foundation\Navigation\Contract;

use App\Foundation\Navigation\Builder\Builder;

/**
 * A domain's contribution to the sitemap.
 */
interface NavigationSource
{
    public function declare(Builder $nav): void;
}
