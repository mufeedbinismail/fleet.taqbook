<?php

namespace App\Inventory\Intent;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\ValueObject\ItemAccounts;
use Illuminate\Http\UploadedFile;

/**
 * The General fields of an item, already validated.
 */
abstract class SaveItemIntent
{
    public function __construct(
        public readonly ShortCode $stockId,
        public readonly string $description,
        public readonly string $longDescription,
        public readonly int $categoryId,
        public readonly int $taxTypeId,
        public readonly string $units,
        public readonly ItemSourceType $type,
        public readonly ItemAccounts $accounts,
        public readonly ?int $dimensionId,
        public readonly ?int $dimension2Id,
        public readonly bool $noSale,
        public readonly bool $noPurchase,
        public readonly bool $editable,
        public readonly ?UploadedFile $image,
    ) {}
}
