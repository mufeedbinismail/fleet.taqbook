<?php

namespace App\Foundation\Component\Table\ValueObject;

use App\Foundation\Component\Table\Enum\ExportFormat;
use App\Foundation\Component\Table\Support\SortExpression;

/**
 * What a client asked a table for.
 *
 * Everything here is a request, not a decision: the keys may name nothing, the page may be past
 * the end, and the page size may be larger than anyone is willing to serve.
 */
final class TableState
{
    /**
     * @param  int  $page  1-based
     * @param  int|null  $perPage  null leaves the declared page size in force
     * @param  array<string, mixed>  $filters  keyed by client key, values exactly as they arrived
     * @param  list<Sort>  $sort  in precedence order
     */
    public function __construct(
        public readonly int $page = 1,
        public readonly ?int $perPage = null,
        public readonly ?string $search = null,
        public readonly array $filters = [],
        public readonly array $sort = [],
        public readonly ?ExportFormat $export = null,
    ) {}

    public function isExport(): bool
    {
        return $this->export !== null;
    }

    public function hasSearch(): bool
    {
        return $this->search !== null && $this->search !== '';
    }

    /**
     * Every part is spelled out, absent ones included, so that comparing two of these never finds
     * a difference between a key left out and the same key written as its fallback.
     *
     * The export format is left out: it says what one request is for rather than where a table is
     * sitting.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'q' => $this->search,
            'sort' => SortExpression::express($this->sort),
            // Cast because an empty PHP array encodes as `[]` rather than as an object.
            'filters' => (object) $this->filters,
        ];
    }
}
