<?php

namespace App\Legacy\Navigation\Condition;

use App\Foundation\Setting\SettingRepository;
use App\Foundation\Navigation\Contract\Condition;

final class FixedAssetsEnabled implements Condition
{
    public function __construct(private readonly SettingRepository $settings) {}

    public function __invoke(): bool
    {
        return $this->settings->useFixedAssetsModule();
    }
}
