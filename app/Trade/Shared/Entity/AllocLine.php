<?php

namespace App\Trade\Shared\Entity;

use App\Finance\Support\MoneyFactory;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Foundation\Shared\ValueObject\TypedId;
use Brick\Money\Money;

/**
 * One allocation row (cust_allocations / supp_allocations share this shape). Links the
 * allocator transaction (the payment/credit doing the allocating) to the allocatee
 * transaction (the invoice it pays down). Keyed on person_id, so it is agnostic of the
 * counter-party type (customer or supplier). Captured in both directions so a document
 * carries every allocation it participates in.
 */
class AllocLine
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $personId,
        public readonly Money $amount,
        public readonly DomainDateTime $dateAlloc,
        public readonly TypedId $allocator,
        public readonly TypedId $allocatee
    ) {}

    public static function fromDbRow(object $row, string $currency): self
    {
        return new self(
            id: (int) $row->id,
            personId: $row->person_id !== null ? (int) $row->person_id : null,
            amount: MoneyFactory::of($row->amt, $currency),
            dateAlloc: DomainDateTime::fromDateString($row->date_alloc),
            allocator: TypedId::make($row->trans_type_from, $row->trans_no_from),
            allocatee: TypedId::make($row->trans_type_to, $row->trans_no_to)
        );
    }
}
