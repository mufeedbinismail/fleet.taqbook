<?php

namespace App\Legacy\Navigation\Condition;

use App\Foundation\Navigation\Contract\Condition;
use App\Foundation\Shared\Setting\GlobalSetting;

final class ManufacturingEnabled implements Condition
{
    public function __construct(private readonly GlobalSetting $settings) {}

    public function __invoke(): bool
    {
        return $this->settings->useManufacturingModule();
    }
}
