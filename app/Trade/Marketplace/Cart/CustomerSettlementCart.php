<?php

namespace App\Trade\Marketplace\Cart;

use App\Finance\Support\MoneyFactory;
use App\Trade\Sale\Entity\CustTransDocument;
use App\Trade\Shared\Collection\DraftAllocationLineCollection;
use App\Trade\Shared\Entity\DraftAllocationLine;
use App\Shared\Enum\SystemType;
use App\Shared\ValueObject\DomainDateTime;
use App\Shared\ValueObject\TypedId;
use Brick\Money\Money;

class CustomerSettlementCart
{
    public readonly string $cartId;

    public TypedId         $transId;
    public ?int            $customerId        = null;
    public ?int            $branchId          = null;
    public ?string         $receivableAccount = null;
    public ?int            $marketplaceId     = null;
    public ?int            $supplierId        = null;
    public ?string         $payableAccount    = null;
    public DomainDateTime  $transDate;
    public ?string         $reference         = null;
    public ?Money          $amount            = null;
    public string          $memo              = '';

    public DraftAllocationLineCollection $lines;

    public ?CustTransDocument $old = null;

    private function __construct(
        TypedId $transId,
        DomainDateTime $transDate,
        DraftAllocationLineCollection $lines
    )
    {
        $this->cartId    = uniqid('');
        $this->transId   = $transId;
        $this->transDate = $transDate;
        $this->lines     = $lines;
    }

    public static function draft(SystemType $transType): static
    {
        return new static (TypedId::make($transType), DomainDateTime::now(), new DraftAllocationLineCollection());
    }

    public static function fromDocument(CustTransDocument $old): static
    {
        $cart = new static($old->id, $old->transDate, new DraftAllocationLineCollection());

        $cart->customerId    = $old->customerId;
        $cart->marketplaceId = $old->marketplaceId;
        $cart->reference     = $old->reference;
        $cart->memo          = $old->memo;
        $cart->amount        = $old->total;
        $cart->old           = $old;

        return $cart;
    }

    public function isEdit(): bool
    {
        return $this->transId->isExisting();
    }

    public function totalAllocated(): Money
    {
        return MoneyFactory::sum($this->lines->column('thisAllocation'));
    }

    public function selectedLines(): DraftAllocationLineCollection
    {
        return $this->lines->filter(fn (DraftAllocationLine $line) => $line->thisAllocation->isPositive());
    }

    public function clearLines(): void
    {
        $this->lines = new DraftAllocationLineCollection();
    }
}
