<?php

namespace App\Inventory\Service;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Repository\ItemRepository;
use LogicException;

class ItemBarcodeService
{
    private const BARCODE_MAX_TRIES = 20;

    public function __construct(private readonly ItemRepository $repository) {}

    /**
     * An EAN-8 no item, sales kit or code alias answers to yet. It is a suggestion: creating the item still checks the code.
     *
     * @throws LogicException when every try met a code already in use
     */
    public function generate(): ShortCode
    {
        for ($try = 0; $try < self::BARCODE_MAX_TRIES; $try++) {
            $digits = [random_int(1, 9), ...array_map(fn () => random_int(0, 9), range(1, 6))];
            $code = new ShortCode(implode('', $digits).$this->ean8CheckDigit($digits));

            if (! $this->repository->isItemCodeTaken($code)) {
                return $code;
            }
        }

        throw new LogicException('No unused barcode found in '.self::BARCODE_MAX_TRIES.' tries.');
    }

    /**
     * Same sum as the legacy generator (items.php): odd positions weigh 3, even positions 1.
     *
     * @param  list<int>  $digits  the first seven digits
     */
    private function ean8CheckDigit(array $digits): int
    {
        $sum = 0;
        foreach ($digits as $index => $digit) {
            $sum += $index % 2 === 0 ? 3 * $digit : $digit;
        }

        return (10 - $sum % 10) % 10;
    }
}
