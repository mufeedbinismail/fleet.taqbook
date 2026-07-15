<?php

namespace App\Trade\Marketplace\Cart;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Entity\TaxSetting;
use App\Shared\Enum\SystemType;
use App\Shared\ValueObject\TypedId;
use Brick\Money\Money;

class SupplierTransCart
{
    public readonly string  $cartId;

    public ?SystemType $transType    = null;
    public int     $transNo          = 0;
    public ?string $marketplaceId    = null;
    public ?int    $supplierId       = null;
    public ?string $provisionAccount = null;
    public ?string $payableAccount   = null;
    public ?string $date             = null;
    public ?string $reference        = null;
    public ?string $supplierRef      = null;
    public string  $receiveInto      = '';
    public string  $receiveAddress   = '';
    public string  $comments         = '';
    public ?TaxSetting $marketplaceTaxSetting = null;

    /** @var SupplierTransCartLine[] */
    public array $line_items = [];

    public function __construct(SystemType $transType = SystemType::SupplierInvoice)
    {
        $this->cartId = uniqid('');
        $this->transType = $transType;
    }

    public function transId(): TypedId
    {
        return new TypedId($this->transType, $this->transNo ?: null);
    }

    public function addLine(SupplierTransCartLine $line): void
    {
        $this->line_items[] = $line;
    }

    public function removeLine(int $index): void
    {
        array_splice($this->line_items, $index, 1);
    }

    public function hasLines(): bool
    {
        return count($this->line_items) > 0;
    }

    public function totalRaw(): Money
    {
        return array_reduce(
            $this->line_items,
            fn (Money $carry, SupplierTransCartLine $line) => $carry->plus($line->rawTotal()),
            MoneyFactory::zero()
        );
    }

    public function totalNet(): Money
    {
        return array_reduce(
            $this->line_items,
            fn (Money $carry, SupplierTransCartLine $line) => $carry->plus($line->netTotal()),
            MoneyFactory::zero()
        );
    }

    public function totalTax(): Money
    {
        return array_reduce(
            $this->line_items,
            fn (Money $carry, SupplierTransCartLine $line) => $carry->plus($line->taxTotal()),
            MoneyFactory::zero()
        );
    }

    public function totalGross(): Money
    {
        return $this->totalNet()->plus($this->totalTax());
    }
}
