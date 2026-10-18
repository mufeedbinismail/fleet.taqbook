<?php

namespace App\Inventory\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Inventory\Intent\CreateItemIntent;
use App\Inventory\Model\Item;

final class CreateItemAction extends SaveItemAction
{
    public function execute(CreateItemIntent $intent): Item
    {
        return $this->saveWithImage($intent, function () use ($intent) {
            $this->validate($intent);

            return $this->repository->createItem($intent);
        });
    }

    private function validate(CreateItemIntent $intent): void
    {
        $this->validateGeneralFields($intent);

        if ($this->repository->isItemCodeTaken($intent->stockId)) {
            throw new ValidationException(__('inventory.item.error.code_taken'), 'stock_id');
        }
    }
}
