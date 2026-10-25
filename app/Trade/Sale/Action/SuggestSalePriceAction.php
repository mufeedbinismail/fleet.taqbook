<?php

namespace App\Trade\Sale\Action;

use App\Finance\Currency\Repository\CurrencyRepository;
use App\Foundation\Framework\Exception\ValidationException;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trade\Sale\Intent\SuggestSalePriceIntent;
use App\Trade\Sale\Repository\SalesTypeRepository;
use App\Trade\Sale\Service\SalePriceService;
use App\Trade\Sale\ValueObject\SellingPrice;
use Brick\Money\Exception\CurrencyConversionException;

final class SuggestSalePriceAction
{
    public function __construct(
        private SalePriceService $service,
        private SalesTypeRepository $salesTypeRepository,
        private CurrencyRepository $currencyRepository,
    ) {}

    public function execute(SuggestSalePriceIntent $intent): SellingPrice
    {
        $this->validate($intent);

        try {
            return $this->service->calculateKitPrice(
                (string) $intent->stockId,
                $intent->currency,
                $intent->salesTypeId,
                null,
            );
        } catch (CurrencyConversionException) {
            throw new ValidationException(
                __('finance.exchange_rate.error.missing', ['currency' => $intent->currency, 'date' => DomainDateTime::now()->format('Y-m-d')]),
                'currency',
            );
        }
    }

    private function validate(SuggestSalePriceIntent $intent): void
    {
        if (! $this->salesTypeRepository->salesTypeExists($intent->salesTypeId)) {
            throw new ValidationException(__('sale.price.error.no_sales_type'), 'sales_type_id');
        }

        if (! $this->currencyRepository->currencyExists($intent->currency)) {
            throw new ValidationException(__('sale.price.error.no_currency'), 'currency');
        }
    }
}
