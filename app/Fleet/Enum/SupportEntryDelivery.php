<?php

namespace App\Fleet\Enum;

use App\Foundation\Framework\Concern\ArrayableEnumConcern;
use App\Foundation\Framework\Concern\HasLabelConcern;
use App\Foundation\Framework\Contract\ArrayableEnumContract;
use App\Foundation\Framework\Contract\HasLabelContract;

/**
 * How a support link reaches the employee: the browser redirected to it, or shown for them to carry.
 */
enum SupportEntryDelivery: string implements ArrayableEnumContract, HasLabelContract
{
    use ArrayableEnumConcern, HasLabelConcern;

    case Redirected = 'redirected';
    case Copied = 'copied';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Redirected->value => __('fleet.deployment.support_entry_delivery.redirected'),
            self::Copied->value => __('fleet.deployment.support_entry_delivery.copied'),
        ];
    }
}
