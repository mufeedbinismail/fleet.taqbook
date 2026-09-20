<?php

namespace App\Fleet\Enum;

use App\Foundation\Framework\Concern\HasLabelConcern;
use App\Foundation\Framework\Contract\HasLabelContract;

enum Hosting: string implements HasLabelContract
{
    use HasLabelConcern;

    case CloudOurs = 'cloud_ours';
    case CloudTheirs = 'cloud_theirs';
    case LocalOnPremise = 'local_on_premise';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::CloudOurs->value => __('fleet.deployment.hosting.cloud_ours'),
            self::CloudTheirs->value => __('fleet.deployment.hosting.cloud_theirs'),
            self::LocalOnPremise->value => __('fleet.deployment.hosting.local_on_premise'),
        ];
    }
}
