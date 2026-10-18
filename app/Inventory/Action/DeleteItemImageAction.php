<?php

namespace App\Inventory\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Repository\ItemImageRepository;
use App\Inventory\Repository\ItemRepository;

final class DeleteItemImageAction
{
    public function __construct(
        private ItemRepository $itemRepository,
        private ItemImageRepository $repository,
    ) {}

    /**
     * Removing an image an item does not have is not an error.
     */
    public function execute(ShortCode $code): void
    {
        if ($this->itemRepository->findCachedByStockId((string) $code) === null) {
            throw new ValidationException(__('inventory.item.error.item_missing'), 'item');
        }

        $this->repository->deleteImage($code);
    }
}
