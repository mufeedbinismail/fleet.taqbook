<?php

namespace Tests\Unit\Foundation\Component\Table;

use App\Foundation\Component\DateRange\Control\DateRangeControl;
use App\Foundation\Component\Select\Control\SelectControl;
use App\Foundation\Component\Select\ValueObject\OptionSource;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Enum\Direction;
use App\Foundation\Component\Table\Enum\Stick;
use App\Foundation\Component\Table\Filter\DateRangeFilter;
use App\Foundation\Component\Table\Filter\ExactFilter;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Support\SortExpression;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\FilterDefinition;
use App\Foundation\Component\Table\ValueObject\Sort;
use App\Foundation\Component\Table\ValueObject\TablePage;
use App\Foundation\Component\Table\ValueObject\TableState;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * One half of what a table says over the wire, against cases stated in neither language.
 */
class WireContractTest extends TestCase
{
    /**
     * *Fails if* the two sides read an ordering differently: an arrow is then drawn over a column
     * the rows are not in, and the reader trusts it.
     */
    #[DataProvider('orderings')]
    public function test_an_ordering_is_read_and_written_the_same_way_on_both_sides(string $expression, array $steps, string $written): void
    {
        $parsed = SortExpression::parse($expression);

        $this->assertSame($steps, array_map(fn (Sort $sort) => $sort->toArray(), $parsed));

        $this->assertSame($written, SortExpression::express(array_map(
            fn (array $step) => new Sort($step['key'], Direction::from($step['direction'])),
            $steps,
        )));
    }

    /**
     * *Fails if* the prefix written into an address is not the prefix read back out of it: a shared
     * link then opens on somebody else's position, or on none, and nothing says so.
     */
    #[DataProvider('addresses')]
    public function test_an_address_is_read_as_the_position_the_browser_wrote(string $name, string $query, array $means): void
    {
        $state = TableRequest::create('/_test/table/list?'.$query)->toState($name);

        $this->assertSame($means, json_decode(json_encode($state->toArray()), true));
    }

    /**
     * *Fails if* either half of the envelope spells an absence as a list: read as one, a lookup by
     * key finds nothing, and every chip disappears from a table that is narrowed.
     */
    public function test_narrowing_by_nothing_is_said_the_same_way_in_both_halves_of_the_envelope(): void
    {
        $this->assertStringContainsString('"filters":{}', json_encode((new TableState)->toArray()));

        $this->assertStringContainsString('"filters":{}', json_encode(
            (new TablePage([], 1, 10, 0, 1, [], []))->toArray(),
        ));
    }

    /**
     * *Fails if* a part of the position a handed page answers for is renamed: read under a name
     * nobody writes, it is a part the table opens without.
     */
    public function test_the_position_a_handed_page_answers_for_names_its_parts_the_way_the_browser_reads_them(): void
    {
        $asked = self::agreement()['envelope']['asked'];

        $state = new TableState(
            page: $asked['page'],
            perPage: $asked['per_page'],
            search: $asked['q'],
            filters: $asked['filters'],
            sort: SortExpression::parse($asked['sort']),
        );

        $this->assertSame($asked, json_decode(json_encode($state->toArray()), true));
    }

    /**
     * *Fails if* either side renames a part of the answer: a rename raises nothing, and a count is
     * reported from a size the rows were not drawn at.
     */
    public function test_an_answer_names_its_parts_the_way_the_browser_reads_them(): void
    {
        $parts = self::agreement()['envelope']['parts'];

        $page = new TablePage(
            rows: $parts['rows'],
            page: $parts['page'],
            perPage: $parts['per_page'],
            total: $parts['total'],
            pages: $parts['pages'],
            sort: array_map(fn (array $step) => new Sort($step['key'], Direction::from($step['direction'])), $parts['sort']),
            filters: $parts['filters'],
            footer: $parts['footer'],
            search: $parts['q'],
        );

        $this->assertSame($parts, json_decode(json_encode($page->toArray()), true));
    }

    /**
     * *Fails if* what a table offers about a field is published under keys the other side does not
     * look under. A funnel then draws no control, a chip loses the heading it belongs to, and a
     * range asks the server for a period longer than the one it offered — each of which is a
     * declaration the reader can see and cannot use.
     *
     * The absences are half the agreement: a group that is `null` says once that there is nothing
     * to answer for, where four keys padded with nulls are four chances to disagree.
     */
    #[DataProvider('declarations')]
    public function test_a_definition_publishes_what_it_offers_the_way_the_browser_reads_it(ColumnDefinition $definition, array $published): void
    {
        $this->assertSame(
            self::ordered($published),
            self::ordered(json_decode(json_encode($definition->toArray()), true)),
        );
    }

    /**
     * *Fails if* what a table publishes about a narrowing no heading carries is not what the other
     * side reads: the reader is then told the rows are narrowed by something they cannot recognise.
     */
    #[DataProvider('offerings')]
    public function test_a_narrowing_no_heading_carries_is_published_the_way_the_browser_reads_it(FilterDefinition $offered, array $published): void
    {
        $this->assertSame(
            self::ordered($published),
            self::ordered(json_decode(json_encode($offered->toArray()), true)),
        );
    }

    // ------------------------------------------------------------------------- the agreement

    public static function declarations(): iterable
    {
        $declared = [
            'a drawn column offering a filter says so under one key' => new ColumnDefinition(
                'group',
                'Group',
                sortable: true,
                filter: new ExactFilter('category_id', SelectControl::simple([1 => 'Hardware', 2 => 'Software'])),
            ),
            'a definition offering no filter says so once' => new ColumnDefinition(
                'material_cost',
                'Cost',
                dataType: DataType::Money,
                default: '—',
                width: '8rem',
                class: 'x-table__cell--tight',
                sticky: Stick::End,
            ),
            'a definition drawing no column says so once' => new ColumnDefinition(
                'deletable',
                dataType: DataType::Boolean,
                visible: false,
                exportable: false,
            ),
            'a filter whose choices are fetched says where from' => new ColumnDefinition(
                'customer',
                'Customer',
                filter: new ExactFilter('debtor_no', SelectControl::lookup(new OptionSource('/customers', minSearch: 2))),
            ),
            'a filter over a period says the longest one it offers' => new ColumnDefinition(
                'visited',
                'When',
                dataType: DataType::DateTime,
                filter: new DateRangeFilter('stamp', new DateRangeControl(accepts: 'Y-m-d', maxDays: 31)),
            ),
            'a filter over a period offering no cap says nothing about one' => new ColumnDefinition(
                'seen',
                'Seen',
                dataType: DataType::Date,
                filter: new DateRangeFilter('last_visit', new DateRangeControl(accepts: 'Y-m-d')),
            ),
        ];

        foreach (self::agreement()['columns'] as $guarantee => $published) {
            yield $guarantee => [
                $declared[$guarantee] ?? throw new LogicException("No definition is declared for [{$guarantee}]."),
                $published,
            ];
        }
    }

    public static function offerings(): iterable
    {
        $declared = [
            'a narrowing no heading carries says so under one key' => new FilterDefinition(
                'family',
                'Family',
                new ExactFilter('category_id', SelectControl::simple([1 => 'Hardware', 2 => 'Software'])),
            ),
        ];

        foreach (self::agreement()['filters'] as $guarantee => $published) {
            yield $guarantee => [
                $declared[$guarantee] ?? throw new LogicException("No narrowing is declared for [{$guarantee}]."),
                $published,
            ];
        }
    }

    public static function orderings(): iterable
    {
        foreach (self::agreement()['sort'] as $guarantee => $case) {
            yield $guarantee => [$case['expression'], $case['steps'], $case['written']];
        }
    }

    public static function addresses(): iterable
    {
        foreach (self::agreement()['address'] as $guarantee => $case) {
            yield $guarantee => [$case['name'], $case['query'], $case['means']];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function agreement(): array
    {
        return json_decode(file_get_contents(test_path('contract/table-wire.json')), true);
    }

    /**
     * Keys sorted throughout, the order they are written in being no part of what either side is
     * held to.
     *
     * @param  array<string, mixed>  $shape
     * @return array<string, mixed>
     */
    private static function ordered(array $shape): array
    {
        $shape = array_map(fn (mixed $value) => is_array($value) ? self::ordered($value) : $value, $shape);

        ksort($shape);

        return $shape;
    }
}
