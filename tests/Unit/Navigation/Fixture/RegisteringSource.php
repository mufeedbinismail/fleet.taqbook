<?php

namespace Tests\Unit\Navigation\Fixture;

use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\Contract\NavigationSource;
use App\Foundation\Navigation\Registry\SourceRegistry;

/**
 * Declares nothing, and reaches for the registry from its constructor — the earliest moment a
 * source can ask for something to be added to the build that is resolving it.
 */
final class RegisteringSource implements NavigationSource
{
    public function __construct(SourceRegistry $registry)
    {
        $registry->register(new ClosureSource(function (Builder $nav): void {}));
    }

    public function declare(Builder $nav): void {}
}
