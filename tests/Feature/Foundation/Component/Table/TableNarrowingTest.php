<?php

namespace Tests\Feature\Foundation\Component\Table;

use App\Foundation\Component\Table\Filter\BooleanFilter;
use App\Foundation\Component\Table\ValueObject\FilterDefinition;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The gate: past it a string that arrived from a client is a value and never a column, an operator
 * or a direction. Read as which rows come back, since asserting on generated SQL would hold the
 * component to how it narrows rather than to what it narrows to.
 */
class TableNarrowingTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsStock;

    /**
     * *Fails if* a key from an address reaches the ordering as itself. The keys turned away are
     * real columns of this very table — one the table genuinely orders by under another name, and
     * one differing from a declared key only in case: a nonsense key would be refused by an
     * implementation that merely looks for punctuation.
     */
    public function test_a_sort_key_naming_no_declaration_never_reaches_the_ordering(): void
    {
        $this->stock('s-2', ['description' => 'Bolt', 'material_cost' => 5]);
        $this->stock('s-1', ['description' => 'Anvil', 'material_cost' => 9]);

        $declared = $this->pageOf($this->table(), $this->asked(['sort' => '-name']));

        $this->assertSame(['s-2', 's-1'], $this->codes($declared), 'A declared key did not order the rows.');

        foreach (['description', 'cogs_account', 'NAME', 'stock_master.stock_id); drop table stock_master; --'] as $refused) {
            $page = $this->pageOf($this->table(), $this->asked(['sort' => $refused]));

            $this->assertSame(['s-1', 's-2'], $this->codes($page), "The key [{$refused}] reached the ordering.");
        }

        // Answering at all is most of the evidence for the last of them; reading again is what
        // tells a statement that never ran from one that ran and was swallowed.
        $this->assertSame(2, StockTable::rows()->count());
    }

    /**
     * *Fails if* the key a filter is asked under reaches the query as a column — the same leak as
     * an undeclared sort key, arriving by the other road.
     */
    public function test_a_filter_constrains_the_column_its_declaration_named(): void
    {
        $this->stock('s-1', ['category_id' => 1]);
        $this->stock('s-2', ['category_id' => 2]);

        $page = $this->pageOf($this->table(), $this->asked(['filter' => ['group' => 2]]));

        $this->assertSame(['s-2'], $this->codes($page));
    }

    /**
     * *Fails if* a set of choices reaches the query as one of them: the table then answers about a
     * narrower set of rows than the one somebody picked, and reads as a table that simply holds
     * fewer than they expected.
     */
    public function test_a_filter_reading_a_set_narrows_by_every_choice_picked(): void
    {
        $this->stock('s-1', ['category_id' => 1]);
        $this->stock('s-2', ['category_id' => 2]);
        $this->stock('s-3', ['category_id' => 5]);

        $page = $this->pageOf($this->table(), $this->asked(['filter' => ['groups' => [1, 5]]]));

        $this->assertSame(['s-1', 's-3'], $this->codes($page));
    }

    /**
     * *Fails if* an untouched control is read as a value, which answers with the rows of whatever
     * an empty string compares equal to. Nought sits alongside because it is what a falsy test
     * discards with them: an id, and a boolean answered "no".
     */
    public function test_a_control_carrying_nothing_narrows_nothing_and_nought_still_narrows(): void
    {
        $this->stock('s-1', ['category_id' => 0, 'inactive' => 0]);
        $this->stock('s-2', ['category_id' => 5, 'inactive' => 1]);

        $both = ['s-1', 's-2'];

        $cases = [
            'nothing asked for at all' => [[], $both],
            'a box nobody typed in' => [['filter' => ['group' => '']], $both],
            'a set nobody picked from' => [['filter' => ['name' => ['']]], $both],
            'a toggle nobody moved' => [['filter' => ['inactive' => '']], $both],
            'an id that is nought' => [['filter' => ['group' => '0']], ['s-1']],
            'a boolean answered no' => [['filter' => ['inactive' => '0']], ['s-1']],
        ];

        foreach ($cases as $case => [$own, $expected]) {
            $page = $this->pageOf($this->table(), $this->asked($own));

            $this->assertSame($expected, $this->codes($page), $case);
        }
    }

    /**
     * *Fails if* the alternatives are appended flat rather than grouped: read as
     * `filter AND a OR b`, any search term at all returns rows the filter had excluded, and the
     * leak looks exactly like a search that works.
     */
    public function test_a_search_term_can_never_widen_past_a_filter(): void
    {
        $this->stock('s-1', ['description' => 'Widget', 'category_id' => 1]);
        $this->stock('s-2', ['description' => 'Widget', 'category_id' => 2]);

        $page = $this->pageOf($this->table(), $this->asked([
            'filter' => ['group' => 1],
            'q' => 'Widget',
        ]));

        $this->assertSame(['s-1'], $this->codes($page));
    }

    /**
     * *Fails if* a wildcard typed into the search box is read as a wildcard: a lone `%` then
     * matches every row in the table, and somebody searching for what is in front of them is given
     * results that have nothing to do with it.
     */
    public function test_a_wildcard_somebody_typed_is_the_character_they_typed(): void
    {
        $this->stock('s-1', ['description' => '50% off']);
        $this->stock('s-2', ['description' => '500 units']);
        $this->stock('s-3', ['description' => 'a_b']);
        $this->stock('s-4', ['description' => 'axb']);
        $this->stock('s-5', ['description' => 'back\\slash']);

        foreach (['50%' => ['s-1'], 'a_b' => ['s-3'], 'back\\slash' => ['s-5']] as $term => $expected) {
            $page = $this->pageOf($this->table(), $this->asked(['q' => $term]));

            $this->assertSame($expected, $this->codes($page), "The term [{$term}] matched something else.");
        }
    }

    /**
     * *Fails if* a narrowing no heading carries is asked for and quietly not honoured: the rows come
     * back unnarrowed, and nothing reports that they were.
     */
    public function test_a_narrowing_no_heading_carries_narrows_the_rows_and_is_reported_in_force(): void
    {
        $this->stock('s-1');
        $this->stock('s-2');
        StockTable::rows()->where('stock_id', 's-2')->update(['no_sale' => 1]);

        $table = $this->table()->filterable(
            new FilterDefinition('discontinued', 'Discontinued', new BooleanFilter('no_sale')),
        );

        $page = $this->pageOf($table, $this->asked(['filter' => ['discontinued' => 1]]));

        $this->assertSame(['s-2'], $this->codes($page));
        $this->assertSame(['discontinued' => true], $page->filters);
    }

    /**
     * *Fails if* a table can be talked into showing rows the definition it was drawn from excluded.
     */
    public function test_a_constraint_the_definition_put_on_its_own_query_is_never_widened(): void
    {
        $this->stock('s-1', ['inactive' => 0]);
        $this->stock('s-2', ['inactive' => 1]);

        $table = $this->table()->of(StockTable::rows()->where('inactive', 0));

        $this->assertSame(['s-1'], $this->codes($this->pageOf($table, $this->asked([]))));
        $this->assertSame([], $this->codes($this->pageOf($table, $this->asked(['filter' => ['inactive' => 1]]))));
    }
}
