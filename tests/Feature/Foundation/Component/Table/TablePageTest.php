<?php

namespace Tests\Feature\Foundation\Component\Table;

use App\Foundation\Component\DateRange\Control\DateRangeControl;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\Filter\DateRangeFilter;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\Sort;
use Closure;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * What a page says about the set behind it, and what a row of it is.
 *
 * Every one is a number, a word or a flag somebody acts on, and wrong, each is wrong quietly.
 */
class TablePageTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsStock;

    // ------------------------------------------------------------------------ the footer

    /**
     * *Fails if* the footer is computed over the page or over the whole table.
     *
     * One case answers for both, and the figure a report is signed off from is neither.
     */
    public function test_a_footer_is_computed_over_everything_the_filters_left_and_only_that(): void
    {
        foreach (range(1, 8) as $index) {
            $this->stock(sprintf('s-%02d', $index), ['category_id' => 1, 'material_cost' => 10]);
        }

        foreach (range(9, 12) as $index) {
            $this->stock(sprintf('s-%02d', $index), ['category_id' => 2, 'material_cost' => 100]);
        }

        $table = $this->table()
            ->perPage(2)
            ->footer(fn ($query) => [['name' => 'Total', 'material_cost' => (float) $query->sum('material_cost')]]);

        $page = $this->pageOf($table, $this->asked(['filter' => ['group' => 1], 'page' => 2]));

        $this->assertSame(2, count($page->rows), 'The page under test was not a page.');
        $this->assertSame(80.0, $page->footer[0]['material_cost']);
    }

    // -------------------------------------------------------------- the size of the set

    /**
     * *Fails if* a reader is told there is more, or less, of the table than there is.
     */
    public function test_the_total_counts_the_narrowed_set_and_the_page_count_follows_it(): void
    {
        foreach (range(1, 5) as $index) {
            $this->stock(sprintf('s-%02d', $index), ['category_id' => 1]);
        }

        foreach (range(6, 12) as $index) {
            $this->stock(sprintf('s-%02d', $index), ['category_id' => 2]);
        }

        $page = $this->pageOf($this->table()->perPage(2), $this->asked(['filter' => ['group' => 1]]));

        $this->assertSame(5, $page->total);
        $this->assertSame(3, $page->pages);
        $this->assertSame(2, count($page->rows));
    }

    /**
     * *Fails if* a page past the end is answered literally: an empty table beneath a count saying
     * there is plenty, which reads as broken data rather than as an address nobody should have
     * bookmarked.
     */
    public function test_a_page_past_the_end_is_answered_with_the_last_page_that_exists(): void
    {
        foreach (range(1, 6) as $index) {
            $this->stock(sprintf('s-%02d', $index));
        }

        $page = $this->pageOf($this->table()->perPage(2), $this->asked(['page' => 9]));

        $this->assertSame(3, $page->page);
        $this->assertSame(['s-05', 's-06'], $this->codes($page));
    }

    /**
     * *Fails if* the count is taken flat: the size of the first group is reported as the size of
     * the whole, and the paging on every grouped report is wrong.
     */
    public function test_a_total_over_a_query_that_groups_counts_the_groups(): void
    {
        foreach (range(1, 4) as $index) {
            $this->stock("a-{$index}", ['category_id' => 1]);
        }

        $this->stock('b-1', ['category_id' => 2]);
        $this->stock('c-1', ['category_id' => 3]);

        $table = app(TableBuilder::class)
            ->of(StockTable::rows()->groupBy('category_id')->select('category_id'))
            ->name(StockTable::NAME)
            ->defaultSort('category_id')
            ->definitions(new ColumnDefinition('category_id', 'Group', sortable: true))
            ->map(fn (object $record) => ['category_id' => (int) $record->category_id]);

        $page = $this->pageOf($table, $this->asked([]));

        $this->assertSame(3, $page->total);
    }

    /**
     * *Fails if* one request is enough to make every row in the table be built at once.
     */
    public function test_a_page_size_larger_than_the_table_serves_is_served_at_the_ceiling(): void
    {
        foreach (range(1, 12) as $index) {
            $this->stock(sprintf('s-%02d', $index));
        }

        $page = $this->pageOf($this->table()->perPage(2, max: 4), $this->asked(['per_page' => 5000]));

        $this->assertSame(4, count($page->rows));
        $this->assertSame(3, $page->pages);
    }

    /**
     * *Fails if* the bounds are compared against the stored value rather than against the day it
     * falls on: everything stamped after midnight on the last day of a period then vanishes out of
     * every report drawn over it, and the report still totals and still balances.
     */
    public function test_a_range_is_compared_by_date_rather_than_by_value(): void
    {
        $this->audited(1, '2024-03-01 00:00:00');
        $this->audited(2, '2024-03-31 14:30:00');
        $this->audited(3, '2024-04-01 00:00:01');

        $page = $this->pageOf($this->audit(), $this->asked([
            'filter' => ['visited' => ['from' => '2024-03-01', 'to' => '2024-03-31']],
        ]));

        $this->assertSame([1, 2], array_column($page->rows, 'trans_no'));
    }

    // ------------------------------------------------------------------- what a row is

    /**
     * *Fails if* an unanswered field is read as an empty one. On a drawn column a value that is
     * missing then cannot be told from one that is blank anywhere downstream; on a flag — deletable,
     * postable, invoiceable — an absence reads as a "no", the button that arm of the mapping was
     * about is not drawn, and the user is quietly denied something they are entitled to.
     */
    public function test_every_declared_field_is_answered_for_on_every_row_or_the_row_is_refused(): void
    {
        $this->stock('s-1');

        $answered = $this->flagged(fn (object $record) => [
            'stock_id' => $record->stock_id,
            'inactive' => $record->inactive,
            'deletable' => null,
        ]);

        $row = $this->pageOf($answered, $this->asked([]))->rows[0];

        $this->assertArrayHasKey('deletable', $row, 'A flag answered null was dropped from the row.');
        $this->assertNull($row['deletable']);

        $silentAboutAFlag = $this->flagged(fn (object $record) => [
            'stock_id' => $record->stock_id,
            'inactive' => $record->inactive,
        ]);

        $silentAboutAColumn = $this->flagged(fn (object $record) => [
            'stock_id' => $record->stock_id,
            'deletable' => true,
        ]);

        $this->assertTrue($this->refuses($silentAboutAFlag), 'A flag no mapping answered for was not refused.');
        $this->assertTrue($this->refuses($silentAboutAColumn), 'A column no mapping answered for was not refused.');
    }

    /**
     * *Fails if* a field declared boolean reaches a client as whatever the legacy schema answered
     * with. A tinyint arrives as the string `"0"`, which is truthy in a browser, so a row nobody
     * may delete is drawn with a delete button — and pressing it is refused somewhere the reader
     * cannot see why.
     *
     * The undrawn one is the point: it is the flag nothing renders and so nothing would reveal.
     * Null sits beside it as the near-miss a blanket cast turns into a "no".
     */
    public function test_a_field_declared_boolean_reaches_a_client_as_one_drawn_or_not(): void
    {
        $this->stock('s-1', ['inactive' => 0]);
        $this->stock('s-2', ['inactive' => 1]);

        $table = $this->flagged(fn (object $record) => [
            'stock_id' => $record->stock_id,
            'inactive' => $record->inactive,
            'deletable' => (string) $record->inactive,
        ]);

        $rows = $this->pageOf($table, $this->asked([]))->rows;

        $this->assertSame([false, true], array_column($rows, 'inactive'));
        $this->assertSame([false, true], array_column($rows, 'deletable'));

        $unanswered = $this->flagged(fn (object $record) => [
            'stock_id' => $record->stock_id,
            'inactive' => null,
            'deletable' => null,
        ]);

        $this->assertNull($this->pageOf($unanswered, $this->asked([]))->rows[0]['deletable']);
    }

    /**
     * *Fails if* the sign is read off what the mapping wrote: every amount that gets formatted for
     * reading loses its marking, and the column that most needs to show a negative stops doing so.
     */
    public function test_sign_is_read_off_the_record_and_not_off_what_the_mapping_made_of_it(): void
    {
        $this->stock('s-1', ['material_cost' => -1234.56]);
        $this->stock('s-2', ['material_cost' => 1234.56]);

        $table = $this->table()->map(fn (object $record) => [
            'stock_id' => $record->stock_id,
            'name' => $record->description,
            'material_cost' => '('.number_format(abs((float) $record->material_cost), 2).')',
            'group' => (int) $record->category_id,
            'groups' => (int) $record->category_id,
            'inactive' => $record->inactive,
        ]);

        $rows = $this->pageOf($table, $this->asked([]))->rows;

        $this->assertSame([true, false], array_column($rows, 'material_cost_negative'));
    }

    // ------------------------------------------------------- what the page says it did

    /**
     * *Fails if* a page reports a narrowing the rows never had. Every one of these reaches the
     * reader as a chip beside an unnarrowed count: they are told the rows in front of them exclude
     * something, and act on a set they believe is smaller than it is.
     *
     * The two turned away are the ones a report worked out beforehand lets through — a key an
     * address outlived, and a live declaration handed a shape it cannot read, which narrows by
     * nothing at all and is otherwise indistinguishable from a filter nobody touched.
     */
    public function test_a_page_reports_the_narrowing_the_rows_actually_had_and_no_other(): void
    {
        $this->stock('s-1', ['category_id' => 1, 'description' => 'Widget']);
        $this->stock('s-2', ['category_id' => 2, 'description' => 'Crate']);

        $page = $this->pageOf($this->table(), $this->asked([
            'filter' => [
                'group' => 1,
                'gone' => 2,
                'name' => ['Widget', 'Crate'],
            ],
        ]));

        $this->assertSame(['s-1'], $this->codes($page), 'The rows were not narrowed as the case describes.');
        $this->assertSame(['group' => '1'], $page->filters);
    }

    /**
     * *Fails if* a term is reported in force over rows nothing was matched against: the reader is
     * shown a table saying it is searched, holding every row there is.
     */
    public function test_a_term_is_reported_searched_only_where_something_was_matched_against_it(): void
    {
        $this->stock('s-1', ['description' => 'Widget']);
        $this->stock('s-2', ['description' => 'Crate']);

        $searched = $this->pageOf($this->table(), $this->asked(['q' => 'Widget']));

        $this->assertSame('Widget', $searched->search);
        $this->assertSame(['s-1'], $this->codes($searched));

        $unsearchable = app(TableBuilder::class)
            ->of(StockTable::rows())
            ->name(StockTable::NAME)
            ->defaultSort('stock_id')
            ->definitions(new ColumnDefinition('stock_id', 'Code'))
            ->map(fn (object $record) => ['stock_id' => $record->stock_id]);

        $page = $this->pageOf($unsearchable, $this->asked(['q' => 'Widget']));

        $this->assertNull($page->search);
        $this->assertSame(['s-1', 's-2'], $this->codes($page));
    }

    /**
     * *Fails if* a range is reported as the ends it happened to be given rather than as the two it
     * fed the query. Left out, an open end reads as a period that was never asked for, and the
     * chip over a half-bounded report claims a period the rows were never held to.
     */
    public function test_a_range_reports_both_its_ends_and_an_open_one_as_null(): void
    {
        $this->audited(1, '2024-02-28 09:00:00');
        $this->audited(2, '2024-03-15 09:00:00');

        $open = $this->pageOf($this->audit(), $this->asked([
            'filter' => ['visited' => ['from' => '2024-03-01']],
        ]));

        $this->assertSame(['visited' => ['from' => '2024-03-01', 'to' => null]], $open->filters);
        $this->assertSame([2], array_column($open->rows, 'trans_no'), 'The open end was not open.');

        $closed = $this->pageOf($this->audit(), $this->asked([
            'filter' => ['visited' => ['from' => '2024-03-01', 'to' => '2024-03-31']],
        ]));

        $this->assertSame(['visited' => ['from' => '2024-03-01', 'to' => '2024-03-31']], $closed->filters);
    }

    // --------------------------------------------------------------- what order it is in

    /**
     * *Fails if* an ordering is reported as the column it ran on. A heading is marked by the key it
     * answers to, so a column name reported instead marks no heading at all — the rows are ordered
     * and every arrow says they are not.
     */
    public function test_an_ordering_is_reported_under_the_key_whose_heading_offers_it(): void
    {
        $this->stock('s-2', ['description' => 'Bolt']);
        $this->stock('s-1', ['description' => 'Anvil']);

        $asked = $this->pageOf($this->table(), $this->asked(['sort' => '-name']));

        $this->assertSame([['key' => 'name', 'direction' => 'desc']], $this->ordering($asked->sort));
        $this->assertSame(['s-2', 's-1'], $this->codes($asked), 'The rows were not in the order under test.');

        // Nothing asked for, so the table's own ordering stands and is reported the same way.
        $standing = $this->pageOf($this->table(), $this->asked([]));

        $this->assertSame([['key' => 'stock_id', 'direction' => 'asc']], $this->ordering($standing->sort));
    }

    /**
     * *Fails if* an ordering by a column no heading offers is reported under the column's own name.
     * The reader chose nothing and has no heading to see it marked on, so what arrives is an arrow
     * over nothing, or a column name said out loud on a side of the wire that never says one.
     */
    public function test_an_ordering_by_a_column_no_heading_offers_is_reported_as_no_ordering(): void
    {
        $this->stock('s-1', ['category_id' => 9]);
        $this->stock('s-2', ['category_id' => 1]);

        $table = app(TableBuilder::class)
            ->of(StockTable::rows())
            ->name(StockTable::NAME)
            ->defaultSort('category_id')
            ->definitions(new ColumnDefinition('stock_id', 'Code', sortable: true))
            ->map(fn (object $record) => ['stock_id' => $record->stock_id]);

        $page = $this->pageOf($table, $this->asked([]));

        $this->assertSame([], $this->ordering($page->sort));
        $this->assertSame(['s-2', 's-1'], $this->codes($page), 'The ordering under test did not run.');
    }

    // ------------------------------------------------------------------------- fixtures

    /**
     * A second table, over the one column in reach that carries a time as well as a day.
     */
    private const AUDITED = 4242;

    private function audited(int $number, string $stamp): void
    {
        DB::table('audit_trail')->insert([
            'type' => 1,
            'trans_no' => $number,
            'user' => 1,
            'stamp' => $stamp,
            'fiscal_year' => self::AUDITED,
            'gl_date' => substr($stamp, 0, 10),
        ]);
    }

    private function audit(): TableBuilder
    {
        return app(TableBuilder::class)
            ->of(DB::table('audit_trail')->where('fiscal_year', self::AUDITED))
            ->name(StockTable::NAME)
            ->defaultSort('trans_no')
            ->definitions(
                new ColumnDefinition('trans_no', 'Number', sortable: true),
                // Pinned rather than left to whoever is logged in, so no case depends on a session.
                new ColumnDefinition('visited', 'When', dataType: DataType::DateTime, filter: new DateRangeFilter('stamp', new DateRangeControl(accepts: 'Y-m-d'))),
            )
            ->map(fn (object $record) => [
                'trans_no' => (int) $record->trans_no,
                'visited' => $record->stamp,
            ]);
    }

    /**
     * A table carrying a field that is neither drawn nor written, which is what a per-row flag is.
     *
     * @param  Closure(object): array<string, mixed>  $mapper
     */
    private function flagged(Closure $mapper): TableBuilder
    {
        return app(TableBuilder::class)
            ->of(StockTable::rows())
            ->name(StockTable::NAME)
            ->defaultSort('stock_id')
            ->definitions(
                new ColumnDefinition('stock_id', 'Code'),
                new ColumnDefinition('inactive', 'Inactive', dataType: DataType::Boolean),
                new ColumnDefinition('deletable', dataType: DataType::Boolean, visible: false, exportable: false),
            )
            ->map($mapper);
    }

    /**
     * Whether reading a page off this table is refused outright.
     */
    private function refuses(TableBuilder $table): bool
    {
        try {
            $this->pageOf($table, $this->asked([]));
        } catch (TableException) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<Sort>  $sort
     * @return list<array{key: string, direction: string}>
     */
    private function ordering(array $sort): array
    {
        return array_map(fn (Sort $step) => $step->toArray(), $sort);
    }
}
