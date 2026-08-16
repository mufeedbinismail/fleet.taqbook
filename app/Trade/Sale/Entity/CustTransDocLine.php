<?php

namespace App\Trade\Sale\Entity;

use App\Finance\Support\MoneyFactory;
use Brick\Money\Money;

/**
 * One debtor_trans_details row — a line of a customer transaction document.
 * Monetary columns are modelled as Money (in the document currency); quantities,
 * percentages and counts stay scalar as they carry no currency.
 */
class CustTransDocLine
{
    public function __construct(
        public readonly int $id,
        public readonly string $stockId,
        public readonly ?string $description,
        public readonly Money $unitPrice,
        public readonly Money $unitTax,
        public readonly float $quantity,
        public readonly float $discountPercent,
        public readonly Money $marketplaceCommission,
        public readonly Money $marketplaceShipping,
        public readonly Money $standardCost,
        public readonly float $qtyDone,
        public readonly ?int $srcId
    ) {}

    public static function fromDbRow(object $row, string $currency): self
    {
        return new self(
            id: (int) $row->id,
            stockId: (string) $row->stock_id,
            description: $row->description !== null ? (string) $row->description : null,
            unitPrice: MoneyFactory::of($row->unit_price, $currency),
            unitTax: MoneyFactory::of($row->unit_tax, $currency),
            quantity: (float) $row->quantity,
            discountPercent: (float) $row->discount_percent,
            marketplaceCommission: MoneyFactory::of($row->marketplace_commission, $currency),
            marketplaceShipping: MoneyFactory::of($row->marketplace_shipping, $currency),
            standardCost: MoneyFactory::of($row->standard_cost, $currency),
            qtyDone: (float) $row->qty_done,
            srcId: $row->src_id !== null ? (int) $row->src_id : null
        );
    }
}
