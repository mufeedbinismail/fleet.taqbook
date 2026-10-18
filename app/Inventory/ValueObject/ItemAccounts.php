<?php

namespace App\Inventory\ValueObject;

use App\Inventory\Enum\ItemAccount;

/**
 * The GL accounts of an item, an empty string standing for one its type has no use for.
 */
final class ItemAccounts
{
    public function __construct(
        public readonly string $sales,
        public readonly string $cogs,
        public readonly string $inventory,
        public readonly string $adjustment,
        public readonly string $wip,
    ) {}

    /**
     * @return array<string, string> the code of every account, keyed by the value of its `ItemAccount`
     */
    public function toArray(): array
    {
        return [
            ItemAccount::Sales->value => $this->sales,
            ItemAccount::Cogs->value => $this->cogs,
            ItemAccount::Inventory->value => $this->inventory,
            ItemAccount::Adjustment->value => $this->adjustment,
            ItemAccount::Wip->value => $this->wip,
        ];
    }
}
