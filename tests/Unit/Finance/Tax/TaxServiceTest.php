<?php

namespace Tests\Unit\Finance\Tax;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Collection\TaxableItemCollection;
use App\Finance\Tax\Collection\TaxGroupLineCollection;
use App\Finance\Tax\Entity\ItemTaxSetting;
use App\Finance\Tax\Entity\TaxGroupLine;
use App\Finance\Tax\Entity\TaxSetting;
use App\Finance\Tax\Entity\TaxableItem;
use App\Finance\Tax\Enum\TaxAlgorithm;
use App\Finance\Tax\Service\TaxService;
use Brick\Math\BigDecimal;
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
    const VAT_5_PERCENT = '1';
    const CONVENIENCE_TAX_2_PERCENT = '2';
    const SHIPPING_TAX_3_PERCENT = '3';
    const EXEMPT_LINE = 'exempt';
    
    private TaxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxService();
    }

    private function taxGroupLineCollection(): TaxGroupLineCollection
    {
        return new TaxGroupLineCollection([
            new TaxGroupLine(self::VAT_5_PERCENT, 'VAT 5%', 'sales', 'purchasing', BigDecimal::of('5'), false),
            new TaxGroupLine(self::CONVENIENCE_TAX_2_PERCENT, 'Convenience Tax 2%', 'sales', 'purchasing', BigDecimal::of('2'), false),
            new TaxGroupLine(self::SHIPPING_TAX_3_PERCENT, 'Shipping Tax 3%', 'sales', 'purchasing', BigDecimal::of('3'), true),
        ]);
    }

    private function taxExclusiveCalculateThenSum(): TaxSetting
    {
        return new TaxSetting(1, false, TaxAlgorithm::CALCULATE_THEN_SUM, $this->taxGroupLineCollection());
    }

    private function taxInclusiveCalculateThenSum(): TaxSetting
    {
        return new TaxSetting(1, true, TaxAlgorithm::CALCULATE_THEN_SUM, $this->taxGroupLineCollection());
    }

    private function taxExclusiveSumThenCalculate(): TaxSetting
    {
        return new TaxSetting(1, false, TaxAlgorithm::SUM_THEN_CALCULATE, $this->taxGroupLineCollection());
    }

    private function taxInclusiveSumThenCalculate(): TaxSetting
    {
        return new TaxSetting(1, true, TaxAlgorithm::SUM_THEN_CALCULATE, $this->taxGroupLineCollection());
    }

    private function itemNotExempt(): ItemTaxSetting
    {
        return new ItemTaxSetting(1, false, [self::SHIPPING_TAX_3_PERCENT]);
    }

    private function itemPartialExempt(): ItemTaxSetting
    {
        return new ItemTaxSetting(1, false, [self::CONVENIENCE_TAX_2_PERCENT, self::SHIPPING_TAX_3_PERCENT]);
    }

    private function itemFullyExempt(): ItemTaxSetting
    {
        return new ItemTaxSetting(1, true, []);
    }

    public function test_zero_returns_zero(): void
    {
        $items = new TaxableItemCollection([
            new TaxableItem($this->itemNotExempt(), MoneyFactory::zero()),
        ]);
        $setting = $this->taxExclusiveCalculateThenSum();
        $draft = $this->service->calculateTaxes($items, $setting);

        foreach ($draft as $line) {
            self::assertTrue($line->tax->isZero());
            self::assertTrue($line->net->isZero());
        }
    }

    public function test_exempt_returns_exempt(): void
    {
        $items = new TaxableItemCollection([
            new TaxableItem($this->itemFullyExempt(), MoneyFactory::of(100)),
        ]);
        $setting = $this->taxExclusiveCalculateThenSum();
        $draft = $this->service->calculateTaxes($items, $setting);
        
        foreach ($draft as $key => $line) {
            self::assertTrue($line->tax->isZero());
            if ($key === self::EXEMPT_LINE) {
                self::assertTrue($line->net->isEqualTo(MoneyFactory::of(100)));
            } else {
                self::assertTrue($line->net->isZero());
            }
        }
    }

    public function test_individual_component_taxes_are_calculated_correctly(): void
    {
        $items = new TaxableItemCollection([
            new TaxableItem($this->itemNotExempt(), MoneyFactory::of(100)),
        ]);
        $setting = $this->taxExclusiveCalculateThenSum();
        $draft = $this->service->calculateTaxes($items, $setting, MoneyFactory::of(10));

        self::assertTrue($draft[self::VAT_5_PERCENT]->tax->isEqualTo(MoneyFactory::of(5)));
        self::assertTrue($draft[self::VAT_5_PERCENT]->net->isEqualTo(MoneyFactory::of(100)));

        self::assertTrue($draft[self::CONVENIENCE_TAX_2_PERCENT]->tax->isEqualTo(MoneyFactory::of(2)));
        self::assertTrue($draft[self::CONVENIENCE_TAX_2_PERCENT]->net->isEqualTo(MoneyFactory::of(100)));

        self::assertTrue($draft[self::SHIPPING_TAX_3_PERCENT]->tax->isEqualTo(MoneyFactory::of("0.3")));
        self::assertTrue($draft[self::SHIPPING_TAX_3_PERCENT]->net->isEqualTo(MoneyFactory::of(10)));
    }

    public function test_individual_net_is_correctly_calculated_when_tax_is_inclusive(): void
    {
        $items = new TaxableItemCollection([
            new TaxableItem($this->itemNotExempt(), MoneyFactory::of(107)),
        ]);
        $setting = $this->taxInclusiveCalculateThenSum();
        $draft = $this->service->calculateTaxes($items, $setting, MoneyFactory::of("10.3"));

        self::assertTrue($draft[self::VAT_5_PERCENT]->tax->isEqualTo(MoneyFactory::of(5)));
        self::assertTrue($draft[self::VAT_5_PERCENT]->net->isEqualTo(MoneyFactory::of(100)));

        self::assertTrue($draft[self::CONVENIENCE_TAX_2_PERCENT]->tax->isEqualTo(MoneyFactory::of(2)));
        self::assertTrue($draft[self::CONVENIENCE_TAX_2_PERCENT]->net->isEqualTo(MoneyFactory::of(100)));

        self::assertTrue($draft[self::SHIPPING_TAX_3_PERCENT]->tax->isEqualTo(MoneyFactory::of("0.3")));
        self::assertTrue($draft[self::SHIPPING_TAX_3_PERCENT]->net->isEqualTo(MoneyFactory::of(10)));
    }

    public function test_multiline_items_with_varying_taxes_are_applied_correctly(): void
    {
        $items = new TaxableItemCollection([
            new TaxableItem($this->itemNotExempt(), MoneyFactory::of(100)),
            new TaxableItem($this->itemPartialExempt(), MoneyFactory::of(100)),
            new TaxableItem($this->itemFullyExempt(), MoneyFactory::of(100)),
        ]);
        $setting = $this->taxExclusiveCalculateThenSum();
        $draft = $this->service->calculateTaxes($items, $setting);

        self::assertTrue($draft[self::VAT_5_PERCENT]->tax->isEqualTo(MoneyFactory::of(10)));
        self::assertTrue($draft[self::VAT_5_PERCENT]->net->isEqualTo(MoneyFactory::of(200)));

        self::assertTrue($draft[self::CONVENIENCE_TAX_2_PERCENT]->tax->isEqualTo(MoneyFactory::of(2)));
        self::assertTrue($draft[self::CONVENIENCE_TAX_2_PERCENT]->net->isEqualTo(MoneyFactory::of(100)));

        self::assertTrue($draft[self::EXEMPT_LINE]->tax->isEqualTo(MoneyFactory::of(0)));
        self::assertTrue($draft[self::EXEMPT_LINE]->net->isEqualTo(MoneyFactory::of(100)));
    }

    public function test_tax_exclusive_rounding_edge_sum_then_vs_calculate_then(): void
    {
        $items = new TaxableItemCollection([
            new TaxableItem($this->itemPartialExempt(), MoneyFactory::of('73.90')),
            new TaxableItem($this->itemPartialExempt(), MoneyFactory::of('58.10')),
        ]);

        $sumThen = $this->service->calculateTaxes($items, $this->taxExclusiveSumThenCalculate());
        $calcThen = $this->service->calculateTaxes($items, $this->taxExclusiveCalculateThenSum());

        // SUM_THEN_CALCULATE: 132.00 * 5% = 6.60
        // CALCULATE_THEN_SUM: (73.90 * 5% = 3.70) + (58.10 * 5% = 2.91) = 6.61
        self::assertTrue($sumThen[self::VAT_5_PERCENT]->tax->isEqualTo(MoneyFactory::of('6.60')));
        self::assertTrue($calcThen[self::VAT_5_PERCENT]->tax->isEqualTo(MoneyFactory::of('6.61')));
        self::assertFalse($sumThen[self::VAT_5_PERCENT]->tax->isEqualTo($calcThen[self::VAT_5_PERCENT]->tax));
    }

    public function test_tax_inclusive_rounding_edge_sum_then_vs_calculate_then(): void
    {
        $items = new TaxableItemCollection([
            new TaxableItem($this->itemPartialExempt(), MoneyFactory::of('77.60')),
            new TaxableItem($this->itemPartialExempt(), MoneyFactory::of('61.01')),
        ]);

        $sumThen = $this->service->calculateTaxes($items, $this->taxInclusiveSumThenCalculate());
        $calcThen = $this->service->calculateTaxes($items, $this->taxInclusiveCalculateThenSum());

        // SUM_THEN_CALCULATE: net 132.00 * 5% = 6.60
        // CALCULATE_THEN_SUM: (77.60 * 5/105 = 3.70) + (61.01 * 5/105 = 2.91) = 6.61
        self::assertTrue($sumThen[self::VAT_5_PERCENT]->tax->isEqualTo(MoneyFactory::of('6.60')));
        self::assertTrue($calcThen[self::VAT_5_PERCENT]->tax->isEqualTo(MoneyFactory::of('6.61')));
        self::assertFalse($sumThen[self::VAT_5_PERCENT]->tax->isEqualTo($calcThen[self::VAT_5_PERCENT]->tax));
    }
}
