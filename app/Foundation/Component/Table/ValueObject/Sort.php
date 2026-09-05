<?php

namespace App\Foundation\Component\Table\ValueObject;

use App\Foundation\Component\Table\Enum\Direction;

/**
 * One step of an ordering: a client-facing key and which way it runs. The key is not a column —
 * what column it stands for is decided by whoever declared the table.
 */
final class Sort
{
    public function __construct(
        public readonly string $key,
        public readonly Direction $direction,
    ) {}

    public static function ascending(string $key): self
    {
        return new self($key, Direction::Ascending);
    }

    public static function descending(string $key): self
    {
        return new self($key, Direction::Descending);
    }

    /**
     * @return array{key: string, direction: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'direction' => $this->direction->value,
        ];
    }
}
