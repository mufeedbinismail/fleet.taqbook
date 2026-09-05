<?php

namespace App\Foundation\Component\Table\ValueObject;

use App\Foundation\Component\Table\Contract\Filter;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Enum\Stick;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\Support\Length;

/**
 * Everything one table offers about one field, said once.
 *
 * Drawing is one of the things it declares rather than what it is: a field that is neither drawn
 * nor written is a row flag, answered for on every row and rendered nowhere.
 */
final class ColumnDefinition
{
    public readonly string $align;

    /**
     * @param  string  $key  the key this definition reads from a row
     * @param  bool|string  $sortable  true orders by the row key itself; a string names the column
     *                                 to order by instead
     * @param  string|null  $width  a number and one unit
     * @param  string|null  $align  'left' | 'right' | 'center'
     *
     * @throws TableException if a width is not a length, or a definition that draws nothing offers
     *                        a filter or an ordering
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label = '',
        public readonly DataType $dataType = DataType::Text,
        public readonly bool|string $sortable = false,
        public readonly ?Filter $filter = null,
        public readonly ?string $default = null,
        public readonly ?string $width = null,
        public readonly bool $visible = true,
        public readonly bool $exportable = true,
        ?string $align = null,
        public readonly string $class = '',
        public readonly ?Stick $sticky = null,
    ) {
        if ($width !== null && ! Length::measurable($width)) {
            throw TableException::widthNotALength($key, $width);
        }

        // A filter and an ordering are both offered from a column heading, so a definition that
        // draws none can offer neither, and offering what cannot be honoured is what this refuses.
        if (! $visible && $filter !== null) {
            throw TableException::filterOnUndrawnDefinition($key);
        }

        if (! $visible && $sortable !== false) {
            throw TableException::sortOnUndrawnDefinition($key);
        }

        $this->align = $align ?? $dataType->align();
    }

    public function sortsBy(): ?string
    {
        return match ($this->sortable) {
            false => null,
            true => $this->key,
            default => $this->sortable,
        };
    }

    /**
     * The two groups are null rather than padded, and `visible` is not published at all: whether
     * `appearance` is there says it, and two keys saying one thing can disagree.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'dataType' => $this->dataType->value,
            'sortable' => $this->sortable !== false,
            'exportable' => $this->exportable,
            'filter' => $this->filter === null ? null : FilterDefinition::published($this->key, $this->filter),
            'appearance' => ! $this->visible ? null : [
                'align' => $this->align,
                'class' => $this->class,
                'default' => $this->default,
                'width' => $this->width,
                'sticky' => $this->sticky?->value,
            ],
        ];
    }
}
