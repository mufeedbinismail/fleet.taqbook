<?php

namespace App\Foundation\Component\Select\Collection;

use App\Foundation\Component\Select\ValueObject\Option;
use Illuminate\Contracts\Support\Arrayable;
use Ramsey\Collection\AbstractCollection;

/**
 * @extends \Ramsey\Collection\AbstractCollection<\App\Foundation\Component\Select\ValueObject\Option>
 */
final class OptionCollection extends AbstractCollection implements Arrayable
{
    public function getType(): string
    {
        return Option::class;
    }

    /**
     * @param  iterable<object>  $rows  selected under the option aliases
     */
    public static function fromDbRows(iterable $rows): self
    {
        $collection = new self;

        foreach ($rows as $row) {
            $collection[] = Option::fromDbRow($row);
        }

        return $collection;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_values(array_map(static fn (Option $option) => $option->toArray(), $this->data));
    }
}
