<?php

namespace App\Inventory\Enum;

use App\Foundation\Framework\Concern\HasLabelConcern;
use App\Foundation\Framework\Contract\HasLabelContract;

/**
 * The GL accounts an item can have, valued by the column that holds each.
 */
enum ItemAccount: string implements HasLabelContract
{
    use HasLabelConcern;

    case Sales = 'sales_account';
    case Cogs = 'cogs_account';
    case Inventory = 'inventory_account';
    case Adjustment = 'adjustment_account';
    case Wip = 'wip_account';

    public static function labels(): array
    {
        return [
            self::Sales->value => __('inventory.item.account.sales_account'),
            self::Cogs->value => __('inventory.item.account.cogs_account'),
            self::Inventory->value => __('inventory.item.account.inventory_account'),
            self::Adjustment->value => __('inventory.item.account.adjustment_account'),
            self::Wip->value => __('inventory.item.account.wip_account'),
        ];
    }
}
