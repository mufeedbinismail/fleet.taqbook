<?php

namespace App\Trade\Sale\Action;

use App\Finance\Currency\Repository\CurrencyRepository;
use App\Foundation\Framework\Exception\ValidationException;
use App\Inventory\Repository\ItemRepository;
use App\Trade\Sale\Repository\SalePriceRepository;
use App\Trade\Sale\Repository\SalesTypeRepository;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Money;

abstract class SaveSalePriceAction
{
    public function __construct(
        protected SalePriceRepository $repository,
        protected SalesTypeRepository $salesTypeRepository,
        protected CurrencyRepository $currencyRepository,
        protected ItemRepository $itemRepository,
    ) {}

    /**
     * @param  int|null  $exceptPriceId  the price being updated, which does not count as a duplicate of itself
     */
    protected function validateSalePrice(
        string $stockId,
        int $salesTypeId,
        string $currency,
        BigDecimal $price,
        ?int $exceptPriceId = null
    ): void {
        if (! $this->salesTypeRepository->salesTypeExists($salesTypeId)) {
            throw new ValidationException(__('sale.price.error.no_sales_type'), 'sales_type_id');
        }

        if (! $this->currencyRepository->currencyExists($currency)) {
            throw new ValidationException(__('sale.price.error.no_currency'), 'currency');
        }

        try {
            // Brick refuses a price with more decimal places than its currency has.
            Money::of($price, $currency);
        } catch (RoundingNecessaryException) {
            throw new ValidationException(__('sale.price.error.too_fine'), 'price');
        }

        if ($this->repository->isSalePriceTaken($stockId, $salesTypeId, $currency, $exceptPriceId)) {
            throw new ValidationException(__('sale.price.error.duplicate'), 'price');
        }
    }
}
