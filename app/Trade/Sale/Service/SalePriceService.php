<?php

namespace App\Trade\Sale\Service;

use App\Finance\Currency\Service\ExchangeRateService;
use App\Finance\Shared\Support\DomainMoney;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Inventory\Repository\ItemRepository;
use App\Trade\Sale\Enum\PriceSource;
use App\Trade\Sale\Repository\SalePriceRepository;
use App\Trade\Sale\Repository\SalesTypeRepository;
use App\Trade\Sale\ValueObject\SellingPrice;
use Brick\Math\BigDecimal;
use Brick\Money\Money;

class SalePriceService
{
    public function __construct(
        private readonly SalePriceRepository $repository,
        private readonly SalesTypeRepository $salesTypeRepository,
        private readonly ItemRepository $itemRepository,
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    /**
     * With `$standard` the item's own price is skipped and its kit components summed, each line rounded to the currency;
     * the date defaults to today.
     *
     * @throws ResourceNotFoundException when the sales type is unknown and no factor is given
     */
    public function calculateKitPrice(
        string $itemCode,
        string $currency,
        int $salesTypeId,
        ?BigDecimal $factor,
        ?DomainDateTime $date = null,
        bool $standard = false,
    ): SellingPrice {
        // Resolved once, so every component of a kit is priced as of the same date.
        $date ??= DomainDateTime::now();

        if (! $standard) {
            $price = $this->calculateItemPrice($itemCode, $currency, $salesTypeId, $factor, $date);

            // A price of zero is a price; only none falls through to the components. Legacy read zero as none only
            // because FrontAccounting's 2010 bulk merge 46c5f7a6 made int 0 the "no price" value.
            if ($price !== null) {
                return $price;
            }
        }

        $total = DomainMoney::zero($currency);

        foreach ($this->itemRepository->findKitLines($itemCode) as $line) {
            $price = $line->hasKitComponent()
                ? $this->calculateKitPrice($line->stockId, $currency, $salesTypeId, $factor, $date, $standard)
                : $this->calculateItemPrice($line->stockId, $currency, $salesTypeId, $factor, $date);

            if ($price !== null) {
                $total = $total->plus($price->value->multipliedBy($line->quantity, DomainMoney::defaultRoundingMode()));
            }
        }

        return new SellingPrice($total, PriceSource::Kit);
    }

    /**
     * The date defaults to today.
     *
     * @return SellingPrice|null null when no price applies
     *
     * @throws ResourceNotFoundException when the sales type is unknown and no factor is given
     */
    public function calculateItemPrice(
        string $stockId,
        string $currency,
        int $salesTypeId,
        ?BigDecimal $factor,
        ?DomainDateTime $date = null,
    ): ?SellingPrice {
        $date ??= DomainDateTime::now();
        $factor ??= $this->salesTypeRepository->getSalesTypeFactor($salesTypeId);

        $markup = settings()->stdCostMarkupPercentage();
        $baseId = settings()->baseSalesTypeId();
        $homeCurrency = DomainMoney::defaultCurrency();
        $roundTo = settings()->itemPriceRoundToMinorUnit();

        $rows = $this->repository->findItemPricesInCurrencies($stockId, $currency, $homeCurrency);

        $prices = [];
        foreach ($rows as $row) {
            $prices[$row->sales_type_id][$row->curr_abrev] = Money::of($row->price, $row->curr_abrev);
        }

        // A stored price is used as entered: round_to was written for prices calculated from cost, and reached stored
        // ones only through FrontAccounting's 2009 bulk merge ebc60010.
        if (isset($prices[$salesTypeId][$currency])) {
            return new SellingPrice($prices[$salesTypeId][$currency], PriceSource::Stored);
        }

        $price = null;
        $source = null;

        if (isset($prices[$baseId][$currency])) {
            $price = $prices[$baseId][$currency]->toRational()->multipliedBy($factor);
            $source = PriceSource::Base;
        } elseif (isset($prices[$salesTypeId][$homeCurrency])) {
            $price = $this->exchangeRateService->convertFromHomeCurrency($prices[$salesTypeId][$homeCurrency], $currency, $date);
            $source = PriceSource::Converted;
        } elseif (isset($prices[$baseId][$homeCurrency])) {
            $price = $this->exchangeRateService->convertFromHomeCurrency(
                $prices[$baseId][$homeCurrency]->toRational()->multipliedBy($factor), $currency, $date
            );
            $source = PriceSource::ConvertedBase;
        } elseif ($rows->isEmpty() && $markup !== null) {
            $markedUp = $this->calculateMarkedUpPrice($stockId, $markup);

            if ($markedUp !== null) {
                $price = $this->exchangeRateService->convertFromHomeCurrency($markedUp, $currency, $date);

                if (! $factor->isZero()) {
                    $price = $price->multipliedBy($factor);
                }

                $source = PriceSource::Markup;
            }
        }

        if ($price === null) {
            return null;
        }

        return new SellingPrice(
            $roundTo === 1 ? DomainMoney::round($price) : DomainMoney::roundToStep($price, $roundTo),
            $source,
        );
    }

    /**
     * The cost plus the markup, rounded to the home currency's places before any conversion.
     *
     * @return Money|null null when the item has no cost to add the markup to
     */
    private function calculateMarkedUpPrice(string $stockId, BigDecimal $markup): ?Money
    {
        $cost = $this->itemRepository->findItemUnitCost($stockId);

        if ($cost === null || $cost->getAmount()->isZero()) {
            return null;
        }

        return DomainMoney::round($cost->multipliedBy(BigDecimal::one()->plus($markup->exactlyDividedBy(100))));
    }
}
