<?php

namespace App\Trade\Sale\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Inventory\Enum\ItemSourceType;
use App\Trade\Sale\Intent\CreateSalePriceIntent;
use Illuminate\Support\Facades\DB;

final class CreateSalePriceAction extends SaveSalePriceAction
{
    /**
     * @return int the new price's id
     */
    public function execute(CreateSalePriceIntent $intent): int
    {
        return DB::transaction(function () use ($intent) {
            $this->validate($intent);

            return $this->repository->createSalePrice($intent);
        });
    }

    private function validate(CreateSalePriceIntent $intent): void
    {
        $stockId = (string) $intent->stockId;
        $item = $this->itemRepository->findItemForUpdate($stockId);

        if ($item === null || ItemSourceType::from($item->mb_flag)->isFixedAsset()) {
            throw new ValidationException(__('inventory.item.error.item_missing'), 'stock_id');
        }

        $this->validateSalePrice($stockId, $intent->salesTypeId, $intent->currency, $intent->price);
    }
}
