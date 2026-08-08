<?php

namespace Tests\Unit\Navigation\Fixture;

use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\Contract\NavigationSource;
use Closure;

/**
 * Lets a case declare its sitemap inline, so a fixture reads next to the assertion that depends
 * on it rather than as a class somewhere else.
 */
final class ClosureSource implements NavigationSource
{
    /**
     * @param  Closure(Builder): void  $declare
     */
    public function __construct(private readonly Closure $declare) {}

    public function declare(Builder $nav): void
    {
        ($this->declare)($nav);
    }
}
