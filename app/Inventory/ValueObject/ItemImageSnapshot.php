<?php

namespace App\Inventory\ValueObject;

/**
 * What an item's image was at one moment, so it can be put back; an item with none has no extension and no contents.
 */
final class ItemImageSnapshot
{
    public function __construct(
        public readonly ?string $extension,
        public readonly ?string $contents,
    ) {}

    public static function none(): self
    {
        return new self(null, null);
    }

    public function isEmpty(): bool
    {
        return $this->extension === null || $this->contents === null;
    }
}
