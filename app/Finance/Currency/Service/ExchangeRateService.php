<?php

namespace App\Finance\Currency\Service;

use App\Finance\Currency\Repository\ExchangeRateRepository;
use App\Finance\Shared\Support\DomainMoney;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Brick\Math\BigRational;
use Brick\Money\AbstractMoney;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\RationalMoney;

class ExchangeRateService
{
    public function __construct(
        private readonly ExchangeRateRepository $repository) {}

    /**
     * The stored rate is used unrounded, whatever the user's exchange-rate decimals.
     */
    public function convertFromHomeCurrency(
        AbstractMoney $amount,
        string $currency,
        DomainDateTime $date
    ): RationalMoney {
        $homeCurrency = DomainMoney::defaultCurrency();
        $amountCurrency = $amount->getCurrency()->getCurrencyCode();

        if ($amountCurrency !== $homeCurrency) {
            throw new \LogicException("Cannot convert from the home currency [{$homeCurrency}]: given amount is in [{$amountCurrency}].");
        }

        // The converter leaves an amount already in the currency alone, so the home currency needs no rate.
        $rate = $currency === $homeCurrency ? null : $this->repository->findLastRateBuy($currency, $date);

        // A currency with no rate is left out of the provider, so Brick's converter refuses it.
        $provider = new ConfigurableProvider;

        if ($rate !== null) {
            // The stored rate is home units per unit of the currency; Brick converts by multiplying.
            $provider->setExchangeRate($homeCurrency, $currency, BigRational::one()->dividedBy($rate));
        }

        return (new CurrencyConverter($provider))->convertToRational($amount, $currency);
    }
}
