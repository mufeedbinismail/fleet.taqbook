<?php

namespace App\Inventory\Enum;

use App\Foundation\Framework\Concern\HasLabelConcern;
use App\Foundation\Framework\Contract\HasLabelContract;

enum ItemSourceType: string implements HasLabelContract
{
    use HasLabelConcern;

    case Manufactured = 'M';
    case Bought = 'B';
    case Service = 'D';
    case FixedAsset = 'F';

    public static function labels(): array
    {
        return [
            self::Manufactured->value => __('inventory.item.source_type.manufactured'),
            self::Bought->value => __('inventory.item.source_type.bought'),
            self::Service->value => __('inventory.item.source_type.service'),
            self::FixedAsset->value => __('inventory.item.source_type.fixed_asset'),
        ];
    }

    public function isFixedAsset(): bool
    {
        return $this === self::FixedAsset;
    }

    public function isStocked(): bool
    {
        return $this !== self::Service;
    }

    /**
     * The accounts an item of this type has.
     *
     * @return list<ItemAccount>
     */
    public function accounts(): array
    {
        $accounts = [
            ItemAccount::Sales,
            ItemAccount::Cogs,
        ];

        if ($this->isStocked()) {
            $accounts[] = ItemAccount::Inventory;
            $accounts[] = ItemAccount::Adjustment;
        }

        if ($this === self::Manufactured) {
            $accounts[] = ItemAccount::Wip;
        }

        return $accounts;
    }
}
