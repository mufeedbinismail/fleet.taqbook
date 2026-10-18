<?php

namespace App\Inventory\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Repository\ItemImageRepository;
use App\Inventory\Repository\ItemRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DeleteItemAction
{
    public function __construct(
        private ItemRepository $repository,
        private ItemImageRepository $imageRepository,
    ) {}

    /**
     * Deletes the item and its picture together: the picture goes last inside the transaction, and is put back if the commit fails.
     */
    public function execute(ShortCode $code): void
    {
        $previous = $this->imageRepository->snapshotImage($code);
        $touched = false;
        $committed = false;

        try {
            DB::transaction(function () use ($code, &$touched) {
                $stored = $this->repository->findItemForUpdate((string) $code);

                if ($stored === null || ItemSourceType::from($stored->mb_flag)->isFixedAsset()) {
                    throw new ValidationException(__('inventory.item.error.item_missing'), 'item');
                }

                if ($this->repository->isItemInUse((string) $code)) {
                    throw new ValidationException(__('inventory.item.error.in_use'), 'item');
                }

                $this->repository->deleteItem($code);

                $touched = true;
                $this->imageRepository->deleteImage($code);
            });

            $committed = true;
        } finally {
            if ($touched && ! $committed) {
                try {
                    $this->imageRepository->restoreImage($code, $previous);
                } catch (Throwable $exception) {
                    // Reported, not thrown, so it cannot hide the failure that made it necessary.
                    report($exception);
                }
            }
        }
    }
}
