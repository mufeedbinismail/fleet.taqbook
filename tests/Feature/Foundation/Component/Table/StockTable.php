<?php

namespace Tests\Feature\Foundation\Component\Table;

use App\Foundation\Component\Select\Control\MultiSelectControl;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Contract\TableDefinition;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Filter\BooleanFilter;
use App\Foundation\Component\Table\Filter\ExactFilter;
use App\Foundation\Component\Table\Filter\InFilter;
use App\Foundation\Component\Table\Filter\TextFilter;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\Table;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * A declaration over rows the suite owns, shared because it is the thing under test rather than
 * anything a case asserts on.
 */
class StockTable implements TableDefinition
{
    /**
     * Written into a column nothing asserts on, so the table is narrowed to its own seeded rows.
     */
    public const MARK = 'TBL-TEST';

    public const NAME = 'stock';

    public static function routeName(): string
    {
        return '_test.stock.list';
    }

    public function table(TableBuilder $tables): Table
    {
        return self::declare($tables)->definition();
    }

    public static function rows(): QueryBuilder
    {
        return DB::table('stock_master')->where('fa_class_id', self::MARK);
    }

    public static function declare(TableBuilder $tables): TableBuilder
    {
        return $tables->of(self::rows())
            ->name(self::NAME)
            ->searchable('description', 'long_description')
            ->defaultSort('stock_id')
            ->perPage(25, max: 100)
            ->definitions(
                new ColumnDefinition('stock_id', 'Code', sortable: true),
                // Ordered and narrowed by a column it is not called, so a key reaching the query
                // as a column is visible as a failure rather than as a coincidence.
                new ColumnDefinition('name', 'Description', sortable: 'description', filter: new TextFilter('description')),
                new ColumnDefinition('material_cost', 'Cost', dataType: DataType::Money, sortable: true),
                new ColumnDefinition('group', 'Group', filter: new ExactFilter('category_id')),
                new ColumnDefinition('groups', 'Groups', filter: new InFilter('category_id', MultiSelectControl::simple([
                    1 => 'Hardware',
                    2 => 'Software',
                    5 => 'Services',
                ]))),
                new ColumnDefinition('inactive', 'Inactive', dataType: DataType::Boolean, filter: new BooleanFilter),
            )
            ->map(fn (object $record) => [
                'stock_id' => $record->stock_id,
                'name' => $record->description,
                'material_cost' => (float) $record->material_cost,
                'group' => (int) $record->category_id,
                'groups' => (int) $record->category_id,
                'inactive' => $record->inactive,
            ]);
    }
}
