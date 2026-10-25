<?php

namespace App\Finance\Shared\Support;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Money\AbstractMoney;
use Brick\Money\Context;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\Money as BrickMoney;
use Brick\Money\RationalMoney;
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
        if (! ($currency = \settings()->homeCurrency())) {
            throw new UnexpectedValueException('Home currency is not set. Configure curr_default in company settings.');
        }

        return $currency;
    }

    public static function defaultRoundingMode(): RoundingMode
    {
        return RoundingMode::HALF_UP;
    }

    public static function round(AbstractMoney $amount): BrickMoney
    {
        return $amount->to(new DefaultContext, self::defaultRoundingMode());
    }

    /**
     * The step counts the currency's minor units: one that divides a whole unit rounds up to its next multiple, any
     * other ends the price in that many minor units, as the next whole unit less the rest (12.34 by 3 is 12.03 in AED
     * and 12.003 in KWD); zero stays zero.
     */
    public static function roundToStep(RationalMoney $amount, int $minorUnits): BrickMoney
    {
        if ($amount->getAmount()->isZero()) {
            return self::zero($amount->getCurrency());
        }

        $pow = 10 ** $amount->getCurrency()->getDefaultFractionDigits();
        $mod = $pow >= $minorUnits ? $pow % $minorUnits : $minorUnits % $pow;

        if ($mod != 0) {
            $rounded = BigRational::of($amount->getAmount()->toScale(0, RoundingMode::CEILING))
                ->minus(BigRational::nd($pow - $minorUnits, $pow));
        } else {
            $steps = BigRational::nd($pow, $minorUnits);
            $rounded = BigRational::of($amount->getAmount()->multipliedBy($steps)->toScale(0, RoundingMode::CEILING))
                ->dividedBy($steps);
        }

        return self::of($rounded, $amount->getCurrency());
    }

    public static function of(
        BigNumber|int|float|string $amount,
        Currency|string|int|null $currency = null,
        ?Context $context = null,
        ?RoundingMode $roundingMode = null,
    ): BrickMoney {
        $currency ??= self::defaultCurrency();
        $context ??= new DefaultContext;
        $roundingMode ??= self::defaultRoundingMode();

        return BrickMoney::of($amount, $currency, $context, $roundingMode);
    }

    public static function ofMinor(
        BigNumber|int|float|string $minorAmount,
        Currency|string|int|null $currency = null,
        ?Context $context = null,
        ?RoundingMode $roundingMode = null,
    ): BrickMoney {
        $currency ??= self::defaultCurrency();
        $context ??= new DefaultContext;
        $roundingMode ??= self::defaultRoundingMode();

        return BrickMoney::ofMinor($minorAmount, $currency, $context, $roundingMode);
    }

    public static function zero(
        Currency|string|int|null $currency = null,
        ?Context $context = null
    ): BrickMoney {
        $currency ??= self::defaultCurrency();
        $context ??= new DefaultContext;

        return BrickMoney::zero($currency, $context);
    }

    public static function create(
        BigNumber $amount,
        Currency $currency,
        Context $context,
        ?RoundingMode $roundingMode = null,
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
