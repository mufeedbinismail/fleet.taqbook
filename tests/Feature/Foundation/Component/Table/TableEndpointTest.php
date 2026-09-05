<?php

namespace Tests\Feature\Foundation\Component\Table;

use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Contract\TableDefinition;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\Table;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Concern\ReadsCsv;
use Tests\TestCase;

/**
 * The one address, where a refusal stops being a value and becomes an answer.
 */
class TableEndpointTest extends TestCase
{
    use DatabaseTransactions;
    use ReadsCsv;
    use SeedsStock;

    protected function setUp(): void
    {
        parent::setUp();

        Route::tableData('_test/stock', StockTable::class);
        Route::tableData('_test/wide', UndrawnFieldStockTable::class);
    }

    /**
     * *Fails if* a narrowing nobody can be held to is refused on the screen and written into a file
     * anyway — the refusal exists to stop the rows being served, not to stop them being drawn.
     */
    public function test_a_refusal_is_a_refusal_before_either_answer(): void
    {
        $this->stock('s-1');

        $unreadable = ['stock' => ['filter' => ['name' => ['a', 'b']]]];

        $this->getJson('/_test/stock/list?'.http_build_query($unreadable))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->getJson('/_test/stock/list?'.http_build_query($unreadable + ['export' => 'csv']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /**
     * *Fails if* the workbook is assembled before the size is weighed: the refusal then costs
     * exactly the memory it exists to protect, and arrives after the first byte has gone out, when
     * it can no longer be shown as a refusal at all.
     */
    public function test_a_set_the_format_cannot_carry_is_refused_before_a_byte_is_written(): void
    {
        config(['table.export.xlsx_rows' => 2]);

        foreach (range(1, 3) as $index) {
            $this->stock("s-{$index}");
        }

        $written = fn () => glob(sys_get_temp_dir().'/table-export-*');
        $before = $written();

        $this->getJson('/_test/stock/list?export=xlsx')
            ->assertStatus(422)
            ->assertJsonValidationErrors('export');

        $this->assertSame($before, $written(), 'A refused export left a file behind.');

        // The same set in the format that can carry it is served, so the refusal is about the size
        // rather than about the table.
        $this->get('/_test/stock/list?export=csv')->assertOk();
    }

    /**
     * *Fails if* an export is served a page at a time, or is narrowed and ordered by something
     * other than what the reader was looking at when they asked for it.
     */
    public function test_an_export_carries_the_whole_narrowed_set_ordered_as_the_page_was(): void
    {
        foreach (range(1, 30) as $index) {
            $this->stock(sprintf('s-%02d', $index), ['category_id' => $index <= 28 ? 1 : 2]);
        }

        $rows = $this->csv($this->get('/_test/stock/list?'.http_build_query([
            'stock' => ['filter' => ['group' => 1], 'sort' => '-stock_id'],
            'export' => 'csv',
        ])));

        // 28 of the 30, past the 25 a page holds, and in the order that was asked for.
        $this->assertSame(28, count($rows));
        $this->assertSame('s-28', $rows[0][0]);
        $this->assertSame('s-01', $rows[27][0]);
    }

    /**
     * *Fails if* the file and the screen disagree about what the table is — somebody exports
     * precisely to get at a column they cannot see, and it is the one thing missing.
     */
    public function test_a_column_kept_off_the_screen_is_still_written_to_the_file(): void
    {
        $this->stock('s-1', ['description' => 'Widget']);

        $lines = $this->lines($this->get('/_test/wide/list?export=csv'));

        $this->assertSame(['Code', 'Description', 'Cost'], $lines[0]);
        $this->assertSame(['s-1', 'Widget', '0'], $lines[1]);
    }

    /**
     * *Fails if* whether this row may be deleted has to be drawn somewhere to be answered, or is
     * written into a file nobody can act on it from.
     *
     * A flag reaching the browser absent reads there as a "no", and the button it was about is not
     * drawn: the user is quietly denied something they are entitled to, and the screen shows no
     * sign that anything went wrong.
     */
    public function test_a_field_that_is_neither_drawn_nor_written_still_reaches_every_row(): void
    {
        $this->stock('s-1', ['description' => 'Widget', 'inactive' => 1]);

        $this->getJson('/_test/wide/list')
            ->assertOk()
            ->assertJsonPath('rows.0.deletable', false);

        $this->assertSame(['Code', 'Description', 'Cost'], $this->lines($this->get('/_test/wide/list?export=csv'))[0]);
    }

    /**
     * *Fails if* a table reads its neighbour's keys. The term below names a row this table holds,
     * so a table reading it answers with the wrong row rather than with an empty set, which would
     * at least look like a mistake.
     */
    public function test_two_tables_on_one_address_read_only_their_own_keys(): void
    {
        $this->stock('s-1', ['description' => 'Widget']);
        $this->stock('s-2', ['description' => 'Crate']);

        $this->getJson('/_test/stock/list?'.http_build_query([
            'stock' => ['q' => 'Widget'],
            'orders' => ['q' => 'Crate', 'page' => 7],
        ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('rows.0.stock_id', 's-1');
    }

    /**
     * @return list<list<string>>
     */
    private function csv(TestResponse $response): array
    {
        return array_values(array_slice($this->lines($response), 1));
    }

    /**
     * @return list<list<string>>
     */
    private function lines(TestResponse $response): array
    {
        $response->assertOk();

        return $this->csvAt($response->baseResponse->getFile()->getPathname());
    }
}

/**
 * The same table with a field written but never drawn, and one that is neither.
 */
class UndrawnFieldStockTable implements TableDefinition
{
    public function table(TableBuilder $tables): Table
    {
        return StockTable::declare($tables)
            ->definitions(
                new ColumnDefinition('stock_id', 'Code'),
                new ColumnDefinition('description', 'Description', visible: false),
                new ColumnDefinition('material_cost', 'Cost', dataType: DataType::Money),
                new ColumnDefinition('deletable', dataType: DataType::Boolean, visible: false, exportable: false),
            )
            ->map(fn (object $record) => [
                'stock_id' => $record->stock_id,
                'description' => $record->description,
                'material_cost' => (float) $record->material_cost,
                'deletable' => (string) (1 - (int) $record->inactive),
            ])
            ->definition();
    }
}
