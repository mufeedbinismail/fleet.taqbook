<?php

namespace App\Trade\Sale\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Trade\Sale\Intent\UpdateSalePriceIntent;
use Illuminate\Support\Facades\DB;

final class UpdateSalePriceAction extends SaveSalePriceAction
{
    public function execute(UpdateSalePriceIntent $intent): void
    {
        DB::transaction(function () use ($intent) {
            $this->validate($intent);

            $this->repository->updateSalePrice($intent);
        });
    }

    private function validate(UpdateSalePriceIntent $intent): void
    {
        $stored = $this->repository->findSalePriceForUpdate($intent->priceId);

        if ($stored === null) {
            throw new ValidationException(__('sale.price.error.price_missing'), 'price');
        }

        // Held so two updates to the same item's prices are checked one after the other.
        $this->itemRepository->findItemForUpdate($stored->stock_id);

        $this->validateSalePrice(
            $stored->stock_id,
            $intent->salesTypeId,
            $intent->currency,
            $intent->price,
            $intent->priceId
        );
    }
}
