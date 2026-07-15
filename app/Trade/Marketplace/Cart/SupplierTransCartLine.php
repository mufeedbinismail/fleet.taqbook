<?php

namespace App\Trade\Marketplace\Cart;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Entity\ItemTaxSetting;
use App\Finance\Tax\Entity\TaxableItem;
use App\Finance\Tax\Entity\TaxableItemSource;
use App\Finance\Tax\ValueObject\TaxBreakdown;
use Brick\Math\BigDecimal;
use Brick\Money\Money;

class SupplierTransCartLine implements TaxableItemSource
{
    public TaxBreakdown $taxBreakdown;

    public function __construct(
        public string $stockId,
        public string $description,
        public string $unit,
        public BigDecimal $qty,
        public Money $amount,
        public ItemTaxSetting $itemTaxSetting,
        ?TaxBreakdown $taxBreakdown = null
    ) {
        $this->taxBreakdown = $taxBreakdown ?? new TaxBreakdown(
            MoneyFactory::zero(),
            $this->toTaxableItem()->price
        );
    }

    public function toTaxableItem(): TaxableItem
    {
        return new TaxableItem($this->itemTaxSetting, $this->rawTotal());
    }

    public function rawTotal(): Money
    {
        return $this->amount->multipliedBy($this->qty, MoneyFactory::defaultRoundingMode());
    }

    public function lineTotal(): Money
    {
        return $this->taxBreakdown->fullPrice();
    }

    public function taxTotal(): Money
    {
        return $this->taxBreakdown->tax;
    }

    public function netTotal(): Money
    {
        return $this->taxBreakdown->net;
    }
}
