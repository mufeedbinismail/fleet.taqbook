<?php

namespace Tests\Unit\Foundation\Navigation\Fixture;

use App\Foundation\Navigation\Contract\Condition;

final class NeverCondition implements Condition
{
    public function __invoke(): bool
    {
        return false;
    }
}
