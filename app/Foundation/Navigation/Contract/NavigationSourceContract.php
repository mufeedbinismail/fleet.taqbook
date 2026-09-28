<?php

namespace App\Foundation\Navigation\Contract;

use App\Foundation\Navigation\Builder\NavigationBuilder;

/**
 * A domain's contribution to the sitemap.
 */
interface NavigationSourceContract
{
    public function declare(NavigationBuilder $nav): void;
}
