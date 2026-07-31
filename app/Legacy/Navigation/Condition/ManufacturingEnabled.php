<?php

namespace App\Legacy\Navigation\Condition;

use App\Foundation\Setting\SettingRepository;
use App\Navigation\Contract\Condition;

final class ManufacturingEnabled implements Condition
{
    public function __construct(private readonly SettingRepository $settings) {}

    public function __invoke(): bool
    {
        return $this->settings->useManufacturingModule();
    }
}
