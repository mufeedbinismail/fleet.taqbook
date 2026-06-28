<?php

namespace App\Trade\Marketplace\Repository;

use App\Shared\Enum\SystemType;
use App\Shared\ValueObject\TypedId;
use App\Trade\Sale\Query\Allocation\AllocateesQuery;
use App\Trade\Shared\Collection\DraftAllocationLineCollection;

class CustAllocRepository
{
    public function getInvoiceAllocatees(
        ?int $customerId = null,
        ?int $marketplaceId = null,
        ?TypedId $ignoreAllocator = null,
        bool $lock = false
    ): DraftAllocationLineCollection
    {
        if (!$customerId || !$marketplaceId) {
            return new DraftAllocationLineCollection();
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
}
