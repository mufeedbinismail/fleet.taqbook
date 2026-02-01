<?php

namespace App\Finance\Tax\Entity;

use App\Finance\Support\MoneyFactory;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Illuminate\Contracts\Support\Arrayable;

final class DraftTaxGroupLine implements Arrayable
{
    public function __construct(
        public readonly string $taxTypeId,
        public readonly string $taxTypeName,
        public readonly string $salesGlCode,
        public readonly string $purchasingGlCode,
        public readonly ?BigDecimal $rate,
        public readonly bool $taxShipping,
        public Money $tax,
        public Money $net
    ) {}

    public static function fromTaxGroupLine(TaxGroupLine $line): self
    {
        return new self(
            $line->taxTypeId,
            $line->taxTypeName,
            $line->salesGlCode,
            $line->purchasingGlCode,
            $line->rate,
            $line->taxShipping,
            MoneyFactory::zero(),
            MoneyFactory::zero()
        );
    }

    public static function exempt(): self
    {
        return new self('', '', '', '', null, false, MoneyFactory::zero(), MoneyFactory::zero());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['tax_type_id'],
            $data['tax_type_name'],
            $data['sales_gl_code'],
            $data['purchasing_gl_code'],
            BigDecimal::of($data['rate']),
            (bool) $data['tax_shipping'],
            MoneyFactory::of($data['Value'] ?? '0'),
            MoneyFactory::of($data['Net'] ?? '0')
        );
    }

    public function toArray(): array
    {
        return [
            'tax_type_id' => $this->taxTypeId,
            'tax_type_name' => $this->taxTypeName,
            'sales_gl_code' => $this->salesGlCode,
            'purchasing_gl_code' => $this->purchasingGlCode,
            'rate' => (string) $this->rate,
            'tax_shipping' => (int) $this->taxShipping,
            'Value' => (string) $this->tax->getAmount(),
            'Net' => (string) $this->net->getAmount()
        ];
    }
}
