<?php

namespace App\Finance\Support;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Money as BrickMoney;
use Brick\Money\Currency;
use UnexpectedValueException;

final class MoneyFactory
{
    /**
     * Resolve default currency from settings. Throws if not set.
     *
     * @throws UnexpectedValueException
     */
    public static function defaultCurrency(): string
    {
        if (! ($currency = \settings()->homeCurrency()) ) {
            throw new UnexpectedValueException('Home currency is not set. Configure curr_default in company settings.');
        }
        return $currency;
    }

    public static function defaultRoundingMode(): RoundingMode
    {
        return RoundingMode::HALF_UP;
    }

    public static function of(
        BigNumber|int|float|string $amount,
        Currency|string|int|null $currency = null,
        Context|null $context = null,
        RoundingMode|null $roundingMode = null,
    ): BrickMoney {
        $currency ??= self::defaultCurrency();
        $context ??= new DefaultContext();
        $roundingMode ??= self::defaultRoundingMode();
        return BrickMoney::of($amount, $currency, $context, $roundingMode);
    }

    public static function ofMinor(
        BigNumber|int|float|string $minorAmount,
        Currency|string|int|null $currency = null,
        Context|null $context = null,
        RoundingMode|null $roundingMode = null,
    ): BrickMoney {
        $currency ??= self::defaultCurrency();
        $context ??= new DefaultContext();
        $roundingMode ??= self::defaultRoundingMode();
        return BrickMoney::ofMinor($minorAmount, $currency, $context, $roundingMode);
    }

    public static function zero(
        Currency|string|int|null $currency = null,
        Context|null $context = null
    ): BrickMoney
    {
        $currency ??= self::defaultCurrency();
        $context ??= new DefaultContext();
        return BrickMoney::zero($currency, $context);
    }

    public static function create(
        BigNumber $amount,
        Currency $currency,
        Context $context,
        RoundingMode|null $roundingMode = null,
    ): BrickMoney {
        $roundingMode ??= self::defaultRoundingMode();
        return BrickMoney::create($amount, $currency, $context, $roundingMode);
    }

    public static function value(BrickMoney $money): string
    {
        return (string) $money->getAmount();
    }

    public static function sum(iterable $monies): BrickMoney
    {
        $sum = null;
        foreach ($monies as $money) {
            if (! $sum) {
                $sum = $money;
            } else {
                $sum = $sum->plus($money);
            }
        }
        return $sum ?? self::zero();
    }
}
