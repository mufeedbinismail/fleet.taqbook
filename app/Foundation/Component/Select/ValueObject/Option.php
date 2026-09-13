<?php

namespace App\Foundation\Component\Select\ValueObject;

use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\Support\DataAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

/**
 * One choosable row.
 *
 * The value is a string because a `<select>` holds no other kind: a value that left as an integer
 * and came back as text would otherwise stop matching the row it came from.
 */
final class Option implements Arrayable
{
    /**
     * What this row carries beyond its own name. Written by whatever defines the list, never
     * chosen by whatever consumes it: naming the columns wanted would be choosing what the query
     * selects.
     *
     * @var array<string, string>
     */
    public readonly array $data;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly ?string $description = null,
        public readonly bool $disabled = false,

        // The heading this row sits under, where the list is one a reader navigates by section
        // rather than by scrolling — null where it has none, which is most lists.
        public readonly ?string $group = null,
        array $data = [],
    ) {
        $this->data = DataAttributes::of($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function of(
        int|string $value,
        string $label,
        ?string $description = null,
        bool $disabled = false,
        ?string $group = null,
        array $data = [],
    ): self {
        return new self((string) $value, $label, $description, $disabled, $group, $data);
    }

    /**
     * Refused rather than defaulted without a value or a label: a default would hide the missing
     * alias.
     *
     * @throws SelectException
     */
    public static function fromDbRow(object $row): self
    {
        $columns = $row instanceof Model ? $row->getAttributes() : get_object_vars($row);

        foreach (['value', 'label'] as $required) {
            if (($columns[$required] ?? null) === null) {
                throw SelectException::rowWithout($required);
            }
        }

        $data = [];
        foreach ($columns as $column => $carried) {
            if (str_starts_with($column, 'data_')) {
                $data[substr($column, 5)] = $carried;
            }
        }

        return new self(
            (string) $columns['value'],
            (string) $columns['label'],
            isset($columns['description']) ? (string) $columns['description'] : null,
            (bool) ($columns['disabled'] ?? false),
            isset($columns['group']) ? (string) $columns['group'] : null,
            $data,
        );
    }

    /**
     * The one wire shape a row has, whichever way it reached the control. `data` is nested rather
     * than spread so a column named like one of the keys above cannot quietly take its place.
     *
     * @return array{value: string, label: string, description: string|null, disabled: bool,
     *               group: string|null, data: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'description' => $this->description,
            'disabled' => $this->disabled,
            'group' => $this->group,
            'data' => $this->data,
        ];
    }

    /**
     * The second line, built from whichever of the parts a row actually has — held apart the same
     * way in every list, so two lists read alike.
     */
    public static function description(?string ...$parts): ?string
    {
        $said = array_filter($parts, static fn (?string $part) => $part !== null && $part !== '');

        // Emptiness decided by whether anything was said, not by what it says: a row whose only
        // part is a code of "0" has a second line, and a falsy test would swallow it.
        return $said === [] ? null : implode(' · ', $said);
    }
}
