<?php

namespace App\Foundation\Component\Table\Builder;

use App\Foundation\Component\Table\Enum\Stick;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\Mapper\RowMapper;
use App\Foundation\Component\Table\Support\SortExpression;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\ColumnSort;
use App\Foundation\Component\Table\ValueObject\FilterDefinition;
use App\Foundation\Component\Table\ValueObject\Table;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Says what one table is prepared to do, a declaration at a time, and settles it into a definition.
 *
 * What a table offers about a field is said once, in that field's own definition, so a key that can
 * be narrowed or ordered by is never offered without a definition behind it. Searching, and a
 * narrowing no field carries, are the exceptions, belonging to the table rather than to any one
 * field.
 */
final class TableBuilder
{
    /** @var list<string> */
    private array $searchable = [];

    /** @var list<ColumnSort> */
    private array $defaultSort = [];

    /** @var list<ColumnDefinition> */
    private array $definitions = [];

    /** @var list<FilterDefinition> */
    private array $filters = [];

    private ?Closure $mapper = null;

    private ?Closure $footer = null;

    private ?string $name = null;

    private bool $markNegatives = true;

    private int $perPage;

    private int $maxPerPage;

    private EloquentBuilder|QueryBuilder|null $query = null;

    public function __construct()
    {
        $this->perPage = (int) config('component.table.per_page');
        $this->maxPerPage = (int) config('component.table.max_per_page');
    }

    /**
     * The query this table is drawn from, which is never widened from here.
     */
    public function of(EloquentBuilder|QueryBuilder $query): self
    {
        $table = clone $this;

        $table->query = $query;

        return $table;
    }

    /**
     * The columns a global search term is matched against, named as columns rather than as keys.
     */
    public function searchable(string ...$columns): self
    {
        $this->searchable = array_values(array_unique([...$this->searchable, ...$columns]));

        return $this;
    }

    /**
     * The ordering used when none is asked for, named as columns rather than as client keys.
     *
     * There is always one: an unordered query may order differently between two readings, and
     * paging over that shows some rows twice and others not at all.
     */
    public function defaultSort(string|ColumnSort ...$sort): self
    {
        $this->defaultSort = array_merge(...array_map(
            fn (string|ColumnSort $step) => $step instanceof ColumnSort ? [$step] : SortExpression::parseColumns($step),
            $sort,
        ));

        return $this;
    }

    /**
     * Without the ceiling, one reading is enough to make every row in the table be built at once.
     */
    public function perPage(int $perPage, ?int $max = null): self
    {
        $this->perPage = $perPage;
        $this->maxPerPage = max($perPage, $max ?? $this->maxPerPage);

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function markNegatives(bool $mark = true): self
    {
        $this->markNegatives = $mark;

        return $this;
    }

    public function definitions(ColumnDefinition ...$definitions): self
    {
        $this->definitions = array_values($definitions);

        return $this;
    }

    public function filterable(FilterDefinition ...$filters): self
    {
        $this->filters = [...$this->filters, ...array_values($filters)];

        return $this;
    }

    /**
     * A mapping answers with values, never with presentation: `1234.56` and a date, not
     * `'1,234.56'` and `'04/03/2024'`.
     *
     * @param  Closure(mixed): (array<string, mixed>|object)  $mapper
     */
    public function map(Closure $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    /**
     * The closure is handed the narrowed but unpaged query, so what it computes is over the whole
     * set the filters left and a page sum is not expressible.
     *
     * @param  Closure(EloquentBuilder|QueryBuilder): list<array<string, mixed>>  $footer
     */
    public function footer(Closure $footer): self
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * @throws TableException if no query or no name was given, if the pinned columns cannot hold an
     *                        edge, if two definitions are ordered by the same column, or if one key
     *                        is offered as a filter twice
     */
    public function definition(): Table
    {
        $this->guardPinning();

        $sortable = [];
        $filterable = [];

        foreach ($this->definitions as $definition) {
            $column = $definition->sortsBy();

            if ($column !== null) {
                // An ordering is reported back under a key worked out from the column it ran on,
                // and two keys on one column leave that working out with a choice to make.
                if (in_array($column, $sortable, true)) {
                    throw TableException::columnOrderedByTwoDefinitions($column);
                }

                $sortable[$definition->key] = $column;
            }

            if ($definition->filter !== null) {
                $filterable[$definition->key] = $definition->filter;
            }
        }

        foreach ($this->filters as $offered) {
            if (isset($filterable[$offered->key])) {
                throw TableException::keyOfferedAsTwoFilters($offered->key);
            }

            $filterable[$offered->key] = $offered->filter;
        }

        return new Table(
            $this->query ?? throw TableException::noQuery(),
            $this->searchable,
            $sortable,
            $filterable,
            $this->defaultSort,
            $this->definitions,
            new RowMapper($this->mapper, $this->definitions, $this->markNegatives),
            $this->perPage,
            $this->maxPerPage,
            $this->name ?? throw TableException::unnamed(),
            $this->filters,
            $this->footer,
        );
    }

    /**
     * Read over the drawn columns rather than all of them, since an undrawn definition is not
     * between anything and an edge.
     *
     * @throws TableException if a run has a gap in it, or a member of one declares no width
     */
    private function guardPinning(): void
    {
        $drawn = array_values(array_filter($this->definitions, fn (ColumnDefinition $definition) => $definition->visible));

        $runs = [];

        foreach ($drawn as $position => $definition) {
            if ($definition->sticky === null) {
                continue;
            }

            if ($definition->width === null) {
                throw TableException::pinnedWithoutWidth($definition->key);
            }

            $runs[$definition->sticky->value][] = $position;
        }

        $start = $runs[Stick::Start->value] ?? [];
        $end = $runs[Stick::End->value] ?? [];

        if ($start !== [] && $start !== range(0, count($start) - 1)) {
            throw TableException::brokenStickyRun(Stick::Start);
        }

        if ($end !== [] && $end !== range(count($drawn) - count($end), count($drawn) - 1)) {
            throw TableException::brokenStickyRun(Stick::End);
        }
    }
}
