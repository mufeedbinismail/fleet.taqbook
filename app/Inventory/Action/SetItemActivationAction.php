<?php

namespace App\Inventory\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Intent\ItemActivationIntent;
use App\Inventory\Repository\ItemRepository;
use Illuminate\Support\Facades\DB;

final class SetItemActivationAction
{
    public function __construct(private ItemRepository $repository) {}

    public function execute(ItemActivationIntent $intent): void
    {
        DB::transaction(function () use ($intent) {
            $stored = $this->repository->findItemForUpdate((string) $intent->stockId);

            if ($stored === null || ItemSourceType::from($stored->mb_flag)->isFixedAsset()) {
                throw new ValidationException(__('inventory.item.error.item_missing'), 'item');
            }

            $this->repository->setItemInactive($intent->stockId, $intent->inactive);
        });
    }
}
