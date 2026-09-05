<?php

namespace Tests\Unit\Foundation\Component\Table;

use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\Filter\BooleanFilter;
use App\Foundation\Component\Table\Filter\ExactFilter;
use App\Foundation\Component\Table\Filter\TextFilter;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\FilterDefinition;
use Closure;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * What a table is refused where it is declared, rather than when somebody asks it for a page.
 *
 * Each of these was expressible before, and none of them announced itself: a declaration that can
 * only be half honoured is honoured for the half that has somewhere to happen, and the half that
 * does not simply never appears. Refused at declaration it is a stack trace on the first render;
 * allowed, it is a funnel nobody can find and an arrow over the wrong heading.
 */
class TableDeclarationTest extends TestCase
{
    /**
     * *Fails if* a definition that draws no column may still offer a filter or an ordering. The
     * key is then honoured by the query and drawn by nothing — no funnel, no arrow, no heading to
     * carry the chip — and the offer exists only in the declaration nobody reads.
     *
     * The two accepted alongside are what a blanket refusal would have taken with them: the row
     * flag, which is a definition drawing nothing and offering nothing, and the ordinary drawn
     * column that offers both.
     */
    public function test_a_definition_drawing_no_column_may_offer_neither_a_funnel_nor_a_sort_arrow(): void
    {
        $offered = fn (array $attributes) => new ColumnDefinition('field', 'Field', ...$attributes);

        $this->assertFalse($this->refuses(fn () => $offered(['sortable' => true, 'filter' => new TextFilter])));
        $this->assertFalse($this->refuses(fn () => $offered(['visible' => false, 'exportable' => false])));

        $this->assertTrue(
            $this->refuses(fn () => $offered(['visible' => false, 'filter' => new TextFilter])),
            'A definition drawing no column was allowed to offer a filter.',
        );

        $this->assertTrue(
            $this->refuses(fn () => $offered(['visible' => false, 'sortable' => true])),
            'A definition drawing no column was allowed to offer an ordering.',
        );

        $this->assertTrue(
            $this->refuses(fn () => $offered(['visible' => false, 'sortable' => 'other_column'])),
            'A definition drawing no column was allowed to name a column to order by.',
        );
    }

    /**
     * *Fails if* two headings may offer an ordering by one column. Which heading an ordering by
     * that column is reported under then has an answer neither the reader nor the table chose, and
     * every page ordered that way marks a heading nobody asked.
     *
     * The pair naming the column outright is the obvious half. The other is the one a check over
     * declared strings alone lets through: a definition ordering by its own key, met by a second
     * naming that key as its column.
     */
    public function test_two_definitions_ordered_by_one_column_are_refused_where_the_table_is_declared(): void
    {
        $this->assertFalse($this->refuses(fn () => $this->settled(
            new ColumnDefinition('code', 'Code', sortable: true),
            new ColumnDefinition('cost', 'Cost', sortable: 'material_cost'),
        )));

        // The near-miss: two headings narrowing one column stay allowed.
        $this->assertFalse($this->refuses(fn () => $this->settled(
            new ColumnDefinition('group', 'Group', filter: new ExactFilter('category_id')),
            new ColumnDefinition('groups', 'Groups', filter: new ExactFilter('category_id')),
        )));

        $this->assertTrue(
            $this->refuses(fn () => $this->settled(
                new ColumnDefinition('cost', 'Cost', sortable: 'material_cost'),
                new ColumnDefinition('price', 'Price', sortable: 'material_cost'),
            )),
            'Two definitions naming one column to order by were allowed.',
        );

        $this->assertTrue(
            $this->refuses(fn () => $this->settled(
                new ColumnDefinition('material_cost', 'Cost', sortable: true),
                new ColumnDefinition('price', 'Price', sortable: 'material_cost'),
            )),
            'A definition ordering by its own key was allowed to be met by one naming that key.',
        );
    }

    /**
     * *Fails if* one key may be offered as a filter by two declarations: the rows are then narrowed
     * by one of them and reported under the other's name.
     *
     * The pair allowed alongside is what a check over columns rather than keys would refuse.
     */
    public function test_one_key_offered_as_a_filter_twice_is_refused_where_the_table_is_declared(): void
    {
        $this->assertFalse($this->refuses(fn () => $this->settled(
            new ColumnDefinition('group', 'Group', filter: new ExactFilter('category_id')),
            new FilterDefinition('family', 'Family', new ExactFilter('category_id')),
        )));

        $this->assertTrue(
            $this->refuses(fn () => $this->settled(
                new ColumnDefinition('inactive', 'Inactive', filter: new BooleanFilter),
                new FilterDefinition('inactive', 'Show inactive', new BooleanFilter('inactive')),
            )),
            'A heading and the table were allowed to offer one key.',
        );

        $this->assertTrue(
            $this->refuses(fn () => $this->settled(
                new FilterDefinition('inactive', 'Show inactive', new BooleanFilter('inactive')),
                new FilterDefinition('inactive', 'Retired', new BooleanFilter('inactive')),
            )),
            'The table was allowed to offer one key twice.',
        );
    }

    /**
     * A table settled out of nothing but the declarations a case is about.
     */
    private function settled(ColumnDefinition|FilterDefinition ...$declared): void
    {
        $definitions = array_filter($declared, fn ($declaration) => $declaration instanceof ColumnDefinition);
        $filters = array_filter($declared, fn ($declaration) => $declaration instanceof FilterDefinition);

        app(TableBuilder::class)
            ->of(DB::table('stock_master'))
            ->name('declared')
            ->definitions(
                new ColumnDefinition('stock_id', 'Code', dataType: DataType::Text),
                ...array_values($definitions),
            )
            ->filterable(...array_values($filters))
            ->definition();
    }

    private function refuses(Closure $declaring): bool
    {
        try {
            $declaring();
        } catch (TableException) {
            return true;
        }

        return false;
    }
}
