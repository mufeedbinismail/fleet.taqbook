<?php

namespace App\Legacy\Navigation\Condition;

use App\Foundation\Navigation\Contract\ConditionContract;
use App\Foundation\Setting\Registry\GlobalSettingRegistry;

final class FixedAssetsEnabled implements ConditionContract
{
    public function __construct(private readonly GlobalSettingRegistry $settings) {}

    public function __invoke(): bool
    {
        return $this->settings->useFixedAssetsModule();
    }
}
