<?php

namespace App\Marketplace\Entities;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Entity\ItemTaxSetting;
use App\Finance\Tax\Entity\TaxSetting;
use App\Finance\Tax\Repository\TaxRepository;
use App\Finance\Tax\Service\TaxService;
use App\Finance\Tax\ValueObject\TaxBreakdown;
use Brick\Money\Money;

class Expense
{
    public string $uuid;
    public string $stockId;
    public string $description;
    public Money $amount;
    
    public ItemTaxSetting $taxSetting;
    public TaxBreakdown $taxBreakdown;

    /**
     * Private constructor to enforce creation via static factory methods.
     * This ensures the object is always created in a clear, defined state.
     * Either as a draft object, or from a db row
     */
    private function __construct(
        string $uuid,
        string $stockId,
        string $description,
        Money $amount,
        ?TaxBreakdown $taxBreakdown = null
    )
    {
        $this->uuid = $uuid;
        $this->stockId = $stockId;
        $this->description = $description;
        $this->amount = $amount;
        $this->taxSetting = (new TaxRepository)->getItemTaxSetting($stockId);
        $this->taxBreakdown = $taxBreakdown ?? new TaxBreakdown(MoneyFactory::zero(), $amount);
    }

    /**
     * Construct a new expense draft
     */
    public static function draft(
        string $uuid,
        string $stockId,
        string $description,
        Money $amount
    ): self {
        return new self($uuid, $stockId, $description, $amount);
    }

    /**
     * Reconstruct an expense from a database row.
     * Only maps fields needed for display/edit — is_voided, tax_inclusive, line_id
     * are DB-query-only concerns and do not belong on the entity.
     */
    public static function fromDbRow(object $row): self
    {
        return new self(
            $row->uuid,
            $row->stock_id,
            $row->description,
            MoneyFactory::of($row->amount),
            new TaxBreakdown(
                MoneyFactory::of($row->tax),
                MoneyFactory::of($row->amount)->minus(MoneyFactory::of($row->tax_inclusive ? $row->tax : 0))
            )
        );
    }
}
