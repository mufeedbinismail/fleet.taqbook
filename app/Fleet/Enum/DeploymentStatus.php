<?php

namespace App\Fleet\Enum;

use App\Foundation\Framework\Concern\HasLabelConcern;
use App\Foundation\Framework\Contract\HasLabelContract;

enum DeploymentStatus: string implements HasLabelContract
{
    use HasLabelConcern;

    case Registered = 'registered';
    case Delivered = 'delivered';
    case Live = 'live';
    case Suspended = 'suspended';
    case Retired = 'retired';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Registered->value => __('fleet.deployment.status.registered'),
            self::Delivered->value => __('fleet.deployment.status.delivered'),
            self::Live->value => __('fleet.deployment.status.live'),
            self::Suspended->value => __('fleet.deployment.status.suspended'),
            self::Retired->value => __('fleet.deployment.status.retired'),
        ];
    }
}
