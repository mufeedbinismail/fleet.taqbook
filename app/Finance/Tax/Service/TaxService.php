<?php

namespace App\Finance\Tax\Service;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Collection\DraftTaxGroupLineCollection;
use App\Finance\Tax\Collection\TaxableItemCollection;
use App\Finance\Tax\Collection\TaxGroupLineCollection;
use App\Finance\Tax\Entity\ItemTaxSetting;
use App\Finance\Tax\Entity\TaxGroupLine;
use App\Finance\Tax\Entity\TaxSetting;
use App\Finance\Tax\Entity\TaxableItem;
use App\Finance\Tax\Enum\TaxAlgorithm;
use App\Finance\Tax\ValueObject\TaxBreakdown;
use Brick\Math\BigDecimal;
use Brick\Money\Money;

class TaxService
{
    public function getTaxBreakdownForItem(
        TaxableItem $taxableItem,
        TaxSetting $taxSetting
    ): TaxBreakdown
    {
        $applicable = $this->filterApplicableTaxLines(
            $taxableItem->itemTaxSetting,
            $taxSetting->taxGroupLines
        );

        return $this->getTaxBreakdown(
            $taxableItem->price,
            $applicable,
            $taxSetting
        );
    }

    public function getTaxBreakdownForShipping(
        Money $shippingCharge,
        TaxSetting $taxSetting
    ): TaxBreakdown
    {
        return $this->getTaxBreakdown(
            $shippingCharge,
            $taxSetting->taxGroupLines->shippingLines(),
            $taxSetting
        );
    }

    public function getTaxBreakdown(
        Money $price,
        TaxGroupLineCollection $applicable,
        TaxSetting $taxSetting
    ): TaxBreakdown
    {
        if ($price->isZero()) {
            return new TaxBreakdown(MoneyFactory::zero(), MoneyFactory::zero());
        }

        if ($applicable->count() === 0) {
            return new TaxBreakdown(MoneyFactory::zero(), $price);
        }

        $tax = MoneyFactory::zero();
        $totalTaxRate = BigDecimal::sum(...$applicable->column('rate'));
        foreach ($applicable as $line) {
            $breakdown = $this->calculateTaxBreakdown(
                $price,
                $line->rate,
                $totalTaxRate,
                $taxSetting->taxIncluded
            );
            $tax = $tax->plus($breakdown->tax);
        }
        
        if ($taxSetting->taxIncluded) {
            return new TaxBreakdown($tax, $price->minus($tax));
        } else {
            return new TaxBreakdown($tax, $price);
        }
    }

    public function calculateTaxes(
        TaxableItemCollection $taxableItems,
        TaxSetting $taxSetting,
        ?Money $shippingCharge = null
    ): DraftTaxGroupLineCollection {
        $shippingCharge ??= MoneyFactory::zero();
        $draft = DraftTaxGroupLineCollection::fromTaxGroupLineCollection($taxSetting->taxGroupLines);
        $isFullyExempt = $taxSetting->taxGroupLines->isFullyExempt();

        $exempt = $draft['exempt'];
        foreach ($taxableItems as $taxableItem) {
            $applicable = $this->filterApplicableTaxLines(
                $taxableItem->itemTaxSetting,
                $taxSetting->taxGroupLines
            );

            if ($isFullyExempt || $applicable->count() === 0) {
                $exempt->net = $exempt->net->plus($taxableItem->price);
                continue;
            }

            $this->distributeTax(
                $taxableItem->price,
                $applicable,
                $taxSetting->taxIncluded,
                $draft
            );
        }

        if (!$shippingCharge->isZero()) {
            $this->distributeTax(
                $shippingCharge,
                $taxSetting->taxGroupLines->shippingLines(),
                $taxSetting->taxIncluded,
                $draft
            );
        }

        if ($taxSetting->taxAlgorithm === TaxAlgorithm::SUM_THEN_CALCULATE) {
            foreach ($taxSetting->taxGroupLines as $line) {
                $draftLine = $draft[$line->taxTypeId];
                $draftLine->tax = $draftLine->net
                    ->toRational()
                    ->multipliedBy($draftLine->rate)
                    ->dividedBy(100)
                    ->to($draftLine->net->getContext(), MoneyFactory::defaultRoundingMode());
            }
        }

        return $draft;
    }

    public function filterApplicableTaxLines(
        ItemTaxSetting $itemTaxSetting,
        TaxGroupLineCollection $taxGroupLines
    ): TaxGroupLineCollection
    {
        if ($itemTaxSetting->fullyExempt) {
            return new TaxGroupLineCollection([]);
        }
        
        return $taxGroupLines->filter(fn (TaxGroupLine $line) => !in_array(
            $line->taxTypeId, $itemTaxSetting->exemptTaxTypeIds
        ));
    }

    public function distributeTax(
        Money $price,
        TaxGroupLineCollection $applicable,
        bool $taxIncluded,
        DraftTaxGroupLineCollection $draft
    ): void
    {
        if ($applicable->count() === 0) {
            return;
        }

        $totalTaxRate = BigDecimal::sum(...$applicable->column('rate'));
        foreach ($applicable as $line) {
            $breakdown = $this->calculateTaxBreakdown($price, $line->rate, $totalTaxRate, $taxIncluded);
            $draftLine = $draft[$line->taxTypeId];
            $draftLine->tax = $draftLine->tax->plus($breakdown->tax);
            $draftLine->net = $draftLine->net->plus($breakdown->net);
        }
    }

    public function calculateTaxBreakdown(
        Money $price,
        BigDecimal $lineTaxRate,
        BigDecimal $totalTaxRate,
        bool $taxIncluded
    ): TaxBreakdown
    {
        if ($price->isZero()) {
            return new TaxBreakdown(MoneyFactory::zero(), MoneyFactory::zero());
        }

        if ($taxIncluded) {
            $taxMultiplier = $lineTaxRate;
            $netMultiplier = 100;
            $divisor = $totalTaxRate->plus(100);
        } else {
            $taxMultiplier = $lineTaxRate;
            $netMultiplier = 100; // In exclusive, Net is just the price, but we can use 100/100
            $divisor = 100;
        }

        $priceRational = $price->toRational();
        $tax = $priceRational
            ->multipliedBy($taxMultiplier)
            ->dividedBy($divisor)
            ->to($price->getContext(), MoneyFactory::defaultRoundingMode());

        $net = $priceRational
            ->multipliedBy($netMultiplier)
            ->dividedBy($divisor)
            ->to($price->getContext(), MoneyFactory::defaultRoundingMode());

        return new TaxBreakdown($tax, $net);
    }
}
