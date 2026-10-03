<?php

namespace App\Trust\Collection;

use App\Trust\Statement\Statement;
use App\Trust\ValueObject\PackedStatement;
use InvalidArgumentException;
use Ramsey\Collection\AbstractCollection;

/**
 * Holds at most one statement of each kind, so a statement is found by its kind alone.
 *
 * @extends AbstractCollection<PackedStatement<Statement>>
 */
final class PackedStatementCollection extends AbstractCollection
{
    public function getType(): string
    {
        return PackedStatement::class;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof PackedStatement && $this->find($value->statement::class) !== null) {
            throw new InvalidArgumentException("Packed statement collection already holds a [{$value->statement::kind()->value}].");
        }

        parent::offsetSet($offset, $value);
    }

    /**
     * @template T of Statement
     *
     * @param  class-string<T>  $class
     * @return PackedStatement<T>|null
     */
    public function find(string $class): ?PackedStatement
    {
        foreach ($this->data as $packed) {
            if ($packed->statement instanceof $class) {
                return $packed;
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $encoded
     */
    public static function fromEncodedStrings(array $encoded): self
    {
        if (! array_is_list($encoded) || array_filter($encoded, fn ($packed) => ! is_string($packed)) !== []) {
            throw new InvalidArgumentException('Packed statement collection must be built from a list of strings.');
        }

        return new self(array_map(PackedStatement::fromEncodedString(...), $encoded));
    }

    /**
     * @return list<string>
     */
    public function toEncodedStrings(): array
    {
        return array_values(array_map(fn (PackedStatement $packed) => $packed->toEncodedString(), $this->data));
    }
}
