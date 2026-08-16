<?php

namespace App\Trade\Marketplace\Repository;

use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\ValueObject\TypedId;
use App\Trade\Sale\Query\Allocation\AllocateesQuery;
use App\Trade\Sale\Query\Allocation\AllocatorsQuery;
use App\Trade\Shared\Collection\DraftAllocationLineCollection;

class CustAllocRepository
{
    public function getInvoiceAllocatees(
        ?int $customerId = null,
        ?int $marketplaceId = null,
        ?TypedId $ignoreAllocator = null,
        bool $lock = false
    ): DraftAllocationLineCollection {
        if (! $customerId || ! $marketplaceId) {
            return new DraftAllocationLineCollection;
        }

        $query = (new AllocateesQuery)
            ->builder($ignoreAllocator)
            ->whereNotNull('trans.marketplace_id')
            ->where('trans.type', SystemType::SalesInvoice->value)
            ->where('trans.debtor_no', $customerId)
            ->where('trans.marketplace_id', $marketplaceId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return DraftAllocationLineCollection::fromDbRows($query->get());
    }

    /**
     * The refund counterpart of getInvoiceAllocatees: open customer credit notes for
     * the given customer + marketplace that can be applied to a refund. $ignoreRefund
     * is the refund being edited (discounts its own consumption so the notes it clears
     * still show with their this_alloc pre-filled).
     */
    public function getCreditNoteAllocators(
        ?int $customerId = null,
        ?int $marketplaceId = null,
        ?TypedId $ignoreRefund = null,
        bool $lock = false
    ): DraftAllocationLineCollection {
        if (! $customerId || ! $marketplaceId) {
            return new DraftAllocationLineCollection;
        }

        $query = (new AllocatorsQuery)
            ->builder($ignoreRefund)
            ->whereNotNull('trans.marketplace_id')
            ->where('trans.type', SystemType::CustomerCredit->value)
            ->where('trans.debtor_no', $customerId)
            ->where('trans.marketplace_id', $marketplaceId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return DraftAllocationLineCollection::fromDbRows($query->get());
    }
}
