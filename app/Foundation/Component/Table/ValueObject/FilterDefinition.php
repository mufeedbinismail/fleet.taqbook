<?php

namespace App\Foundation\Component\Table\ValueObject;

use App\Foundation\Component\Table\Contract\Filter;

/**
 * A narrowing the table offers on its own, no heading drawing it, with the words it is shown under.
 */
final class FilterDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly Filter $filter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'filter' => self::published($this->key, $this->filter),
        ];
    }

    /**
     * Stated first so every filter answers under the same keys, and so a control still decides the
     * values of the ones it declares.
     *
     * @return array<string, mixed>
     */
    public static function published(string $key, Filter $filter): array
    {
        return [
            'key' => $key,
            'control' => null,
            'options' => [],
            'source' => null,
            ...$filter->config(),
        ];
    }
}
