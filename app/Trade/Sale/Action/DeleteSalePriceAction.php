<?php

namespace App\Trade\Sale\Action;

use App\Foundation\Framework\Exception\ValidationException;
use App\Trade\Sale\Repository\SalePriceRepository;
use Illuminate\Support\Facades\DB;

final class DeleteSalePriceAction
{
    public function __construct(private SalePriceRepository $repository) {}

    public function execute(int $priceId): void
    {
        DB::transaction(function () use ($priceId) {
            if ($this->repository->findSalePriceForUpdate($priceId) === null) {
                throw new ValidationException(__('sale.price.error.price_missing'), 'price');
            }

            $this->repository->deleteSalePrice($priceId);
        });
    }
}
