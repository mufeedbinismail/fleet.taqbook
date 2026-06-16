<?php

namespace App\Trade\Shared\Entity;

use App\Finance\Support\MoneyFactory;
use App\Shared\ValueObject\DomainDateTime;
use App\Shared\ValueObject\TypedId;
use Brick\Money\Money;

class DraftAllocationLine
{
    public function __construct(
        public readonly TypedId        $transId,
        public readonly string         $reference,
        public readonly DomainDateTime $transDate,
        public readonly Money          $total,
        public readonly Money          $allocated,
        public readonly Money          $outstanding,
        public          Money          $thisAllocation
    ) {}

    public static function fromDbRow(object $row): self
    {
        return new self(
            transId:        TypedId::make($row->trans_type, $row->trans_no),
            reference:      (string) $row->reference,
            transDate:      DomainDateTime::fromDateString($row->tran_date),
            total:          MoneyFactory::of($row->total, $row->currency),
            allocated:      MoneyFactory::of($row->alloc, $row->currency),
            outstanding:    MoneyFactory::of($row->outstanding, $row->currency),
            thisAllocation: MoneyFactory::of($row->this_alloc, $row->currency)
        );
    }
}
