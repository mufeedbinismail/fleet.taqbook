<?php

namespace App\Trade\Sale\Entity;

use App\Finance\Support\MoneyFactory;
use App\Shared\ValueObject\DomainDateTime;
use App\Shared\Enum\TransactionEffect;
use App\Shared\ValueObject\TypedId;
use App\Trade\Sale\Collection\CustTransDocLineCollection;
use App\Trade\Sale\Enum\PaymentMethod;
use App\Trade\Shared\Collection\AllocLineCollection;
use App\Trade\Shared\Enum\CustomerTransactionSource;
use Brick\Money\Money;

/**
 * A self-contained snapshot of a customer transaction: the full debtor_trans row
 * (with the customer currency and memo joined in) plus its detail lines and the
 * allocations it participates in. Holding the whole aggregate means callers — e.g.
 * history logging — never have to issue follow-up reads.
 *
 * Named "...Document" rather than "...Transaction" to avoid collision with a future
 * Eloquent model for the same table.
 */
class CustTransDocument
{
    public function __construct(
        public readonly int            $rowId,
        public readonly TypedId        $id,
        public readonly CustomerTransactionSource $source,
        public readonly int            $version,
        public readonly ?int           $marketplaceId,
        public readonly int            $customerId,
        public readonly int            $branchId,
        public readonly DomainDateTime $transDate,
        public readonly DomainDateTime $dueDate,
        public readonly string         $reference,
        public readonly ?string        $trackingNo,
        public readonly int            $salesType,
        public readonly int            $orderNo,
        public readonly Money          $overAmount,
        public readonly Money          $overGst,
        public readonly Money          $overFreight,
        public readonly Money          $overFreightTax,
        public readonly Money          $overDiscount,
        public readonly Money          $overMktCost,
        public readonly Money          $total,
        public readonly Money          $allocated,
        public readonly Money          $prepAmount,
        public readonly float          $rate,
        public readonly TransactionEffect $effect,
        public readonly ?int           $shipVia,
        public readonly int            $dimensionId,
        public readonly int            $dimension2Id,
        public readonly ?int           $paymentTermsId,
        public readonly ?PaymentMethod $paymentMethod,
        public readonly bool           $taxIncluded,
        public readonly string         $currency,
        public readonly string         $memo,
        public readonly CustTransDocLineCollection $lines,
        public readonly AllocLineCollection        $allocations
    ) {}

    public static function fromDbRow(
        object $row,
        CustTransDocLineCollection $lines,
        AllocLineCollection $allocations
    ): self {
        $currency = (string) $row->currency;

        return new self(
            rowId:           (int) $row->id,
            id:              TypedId::make($row->type, $row->trans_no),
            source:          CustomerTransactionSource::from((int) $row->source),
            version:         (int) $row->version,
            marketplaceId:   $row->marketplace_id !== null ? (int) $row->marketplace_id : null,
            customerId:      (int) $row->debtor_no,
            branchId:        (int) $row->branch_code,
            transDate:       DomainDateTime::fromDateString($row->tran_date),
            dueDate:         DomainDateTime::fromDateString($row->due_date),
            reference:       (string) $row->reference,
            trackingNo:      $row->tracking_no !== null ? (string) $row->tracking_no : null,
            salesType:       (int) $row->tpe,
            orderNo:         (int) $row->order_,
            overAmount:      MoneyFactory::of($row->ov_amount, $currency),
            overGst:         MoneyFactory::of($row->ov_gst, $currency),
            overFreight:     MoneyFactory::of($row->ov_freight, $currency),
            overFreightTax:  MoneyFactory::of($row->ov_freight_tax, $currency),
            overDiscount:    MoneyFactory::of($row->ov_discount, $currency),
            overMktCost:     MoneyFactory::of($row->ov_mkt_cost, $currency),
            total:           MoneyFactory::of($row->total ?? 0, $currency),
            allocated:       MoneyFactory::of($row->alloc, $currency),
            prepAmount:      MoneyFactory::of($row->prep_amount, $currency),
            rate:            (float) $row->rate,
            effect:          TransactionEffect::from((int) $row->effect),
            shipVia:         $row->ship_via !== null ? (int) $row->ship_via : null,
            dimensionId:     (int) $row->dimension_id,
            dimension2Id:    (int) $row->dimension2_id,
            paymentTermsId:  $row->payment_terms !== null ? (int) $row->payment_terms : null,
            paymentMethod:   $row->payment_method_id !== null ? PaymentMethod::from((int) $row->payment_method_id) : null,
            taxIncluded:     (bool) $row->tax_included,
            currency:        $currency,
            memo:            (string) ($row->memo ?? ''),
            lines:           $lines,
            allocations:     $allocations
        );
    }
}
