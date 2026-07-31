<?php

namespace Tests\Unit\Navigation\Fixture;

use App\Navigation\Contract\Condition;

final class NeverCondition implements Condition
{
    public function __invoke(): bool
    {
        return false;
    }
}
