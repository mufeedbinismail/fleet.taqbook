<?php

namespace App\Foundation\Component\Table\Mapper;

use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Framework\Support\Arr;
use Closure;

/**
 * Turns one record into the row a client receives, adding a `<key>_negative` for each money key
 * whose sign is answered for.
 *
 * A row is answered for in full or not at all: every declared definition is present on what comes
 * back, drawn or not, so a missing value never has to be told apart from an empty one later.
 */
final class RowMapper
{
    /**
     * @param  Closure(mixed): (array<string, mixed>|object)|null  $mapper
     * @param  list<ColumnDefinition>  $definitions  every key a row answers for
     */
    public function __construct(
        private readonly ?Closure $mapper = null,
        private readonly array $definitions = [],
        private readonly bool $marksNegatives = true,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws TableException if what came back cannot be read as a row, or does not answer for a
     *                        definition the row was declared to carry
     */
    public function map(mixed $record): array
    {
        $row = $this->mapper === null ? $record : ($this->mapper)($record);
        $row = Arr::from($row) ?? throw TableException::unmappableRow(get_debug_type($row));

        foreach ($this->definitions as $definition) {
            $key = $definition->key;

            // A key answered with null was answered for; only an absent one is refused.
            if (! array_key_exists($key, $row)) {
                throw TableException::unansweredDefinition($key);
            }

            /*
                A tinyint arrives as 1 or "1" depending on how the query was built, and "0" is
                truthy in JavaScript. Null is left standing, being an answer rather than a no.
            */
            if ($definition->dataType === DataType::Boolean && $row[$key] !== null) {
                $row[$key] = (bool) $row[$key];
            }

            /*
                Read off the record rather than the mapped row, so however a mapping formats an
                amount the sign is still answerable — and off the record itself rather than an array
                made of it, a record being free to keep an attribute out of its array form.
            */
            if ($this->marksNegatives && $definition->dataType === DataType::Money) {
                $value = is_array($record) ? ($record[$key] ?? null) : ($record->{$key} ?? null);

                $row[$key.'_negative'] = is_numeric($value) && (float) $value < 0;
            }
        }

        return $row;
    }
}
