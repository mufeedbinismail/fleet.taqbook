<?php

namespace App\Foundation\Component\Table\ValueObject;

use App\Foundation\Component\Table\DTO\AppliedState;

final class TablePage
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<Sort>  $sort  in precedence order
     * @param  array<string, mixed>  $filters  keyed by client key
     * @param  list<array<string, mixed>>  $footer  rows belonging to the whole set, keyed like rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $pages,
        public readonly array $sort,
        public readonly array $filters,
        public readonly array $footer = [],
        public readonly ?string $search = null,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $footer
     */
    public static function of(array $rows, PagePosition $position, AppliedState $applied, array $footer = []): self
    {
        return new self(
            $rows,
            $position->page,
            $position->perPage,
            $position->total,
            $position->pages,
            $applied->sort,
            $applied->filters,
            $footer,
            $applied->search,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rows' => $this->rows,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'pages' => $this->pages,
            'footer' => $this->footer,
            'q' => $this->search,
            'sort' => array_map(fn (Sort $sort) => $sort->toArray(), $this->sort),
            // Cast because an empty PHP array encodes as `[]` rather than as an object.
            'filters' => (object) $this->filters,
        ];
    }
}
