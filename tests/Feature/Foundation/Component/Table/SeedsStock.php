<?php

namespace Tests\Feature\Foundation\Component\Table;

use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Component\Table\ValueObject\TablePage;
use App\Foundation\Component\Table\ValueObject\TableState;
use Illuminate\Support\Facades\DB;

trait SeedsStock
{
    private function stock(string $id, array $row = []): void
    {
        DB::table('stock_master')->insert([
            'stock_id' => $id,
            'description' => $row['description'] ?? $id,
            'long_description' => $row['long_description'] ?? '',
            'category_id' => $row['category_id'] ?? 1,
            'material_cost' => $row['material_cost'] ?? 0,
            'inactive' => $row['inactive'] ?? 0,
            'depreciation_start' => $row['depreciation_start'] ?? '2024-01-01',
            'fa_class_id' => StockTable::MARK,
        ]);
    }

    /**
     * Built out of a query string rather than by hand, so a case asks for what an address asks for.
     */
    private function asked(array $own, string $name = StockTable::NAME): TableState
    {
        return TableRequest::create('/_test/stock/list?'.http_build_query([$name => $own]))
            ->toState($name);
    }

    private function table(): TableBuilder
    {
        return StockTable::declare(app(TableBuilder::class));
    }

    private function pageOf(TableBuilder $table, TableState $state): TablePage
    {
        return app(TableRepository::class)->page($table->definition(), $state);
    }

    /**
     * @return list<string>
     */
    private function codes(TablePage $page): array
    {
        return array_column($page->rows, 'stock_id');
    }
}
