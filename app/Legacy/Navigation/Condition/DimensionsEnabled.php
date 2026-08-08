<?php

namespace App\Legacy\Navigation\Condition;

use App\Foundation\Shared\Setting\GlobalSetting;
use App\Foundation\Navigation\Contract\Condition;

final class DimensionsEnabled implements Condition
{
    public function __construct(private readonly GlobalSetting $settings) {}

    public function __invoke(): bool
    {
        return $this->settings->dimensionLevel() > 0;
    }
}
