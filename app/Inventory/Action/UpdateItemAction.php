<?php

namespace App\Inventory\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Intent\UpdateItemIntent;
use App\Inventory\Model\Item;

final class UpdateItemAction extends SaveItemAction
{
    public function execute(UpdateItemIntent $intent): Item
    {
        return $this->saveWithImage($intent, function () use ($intent) {
            $this->validate($intent);

            return $this->repository->updateItem($intent);
        });
    }

    private function validate(UpdateItemIntent $intent): void
    {
        $this->validateGeneralFields($intent);

        $stored = $this->lockItem($intent);

        if ($this->repository->isItemInUse((string) $intent->stockId)) {
            $this->refuseLockedFieldChange($stored, $intent);
        }
    }

    /**
     * The type and the unit are fixed once stock, an order line, a bill of materials, a kit or an alias refers to the item.
     */
    private function refuseLockedFieldChange(Item $stored, UpdateItemIntent $intent): void
    {
        $stored->fill(['mb_flag' => $intent->type->value, 'units' => $intent->units]);

        $field = array_key_first($stored->getDirty());

        if ($field !== null) {
            throw new ValidationException(__($field === 'mb_flag' ? 'inventory.item.error.locked_type' : 'inventory.item.error.locked_unit'), $field);
        }
    }

    /**
     * Locks the row until the transaction ends, and refuses an item that is missing or is a fixed asset.
     */
    private function lockItem(UpdateItemIntent $intent): Item
    {
        $stored = $this->repository->findItemForUpdate((string) $intent->stockId);

        if ($stored === null || ItemSourceType::from($stored->mb_flag)->isFixedAsset()) {
            throw new ValidationException(__('inventory.item.error.item_missing'), 'item');
        }

        return $stored;
    }
}
