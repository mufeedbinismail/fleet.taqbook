<?php

namespace App\Inventory\Query;

use App\Inventory\Enum\ItemSourceType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Every item, fixed assets among them, with every column of its row, the names of everything it points at, its price for the base sales type in the home currency, and whether anything refers to it.
 */
class ItemDetailQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null}  $filters  the items asked for, a missing or null one not narrowing at all
     * @param  'items'|'fixed_assets'|null  $mode  `items` for those that are bought, made or sold, `fixed_assets` for the fixed assets, none for both
     */
    public function builder(array $filters = [], ?string $mode = null): Builder
    {
        $baseSalesTypeId = settings()->baseSalesTypeId();
        $homeCurrency = settings()->homeCurrency();

        $query = DB::table('stock_master as stock')
            ->leftJoin('stock_category as category', 'category.category_id', '=', 'stock.category_id')
            ->leftJoin('item_units as unit', 'unit.abbr', '=', 'stock.units')
            ->leftJoin('item_tax_types as tax_type', 'tax_type.id', '=', 'stock.tax_type_id')
            ->leftJoin('dimensions as dimension', 'dimension.id', '=', 'stock.dimension_id')
            ->leftJoin('dimensions as dimension2', 'dimension2.id', '=', 'stock.dimension2_id')
            ->select(
                'stock.stock_id',
                'stock.description',
                'stock.long_description',
                'stock.mb_flag',
                'stock.inactive',
                'stock.no_sale',
                'stock.no_purchase',
                'stock.editable',
                'stock.category_id',
                'category.description as category_name',
                'stock.tax_type_id',
                'tax_type.name as tax_type_name',
                'stock.units',
                'unit.name as unit_name',
                'stock.dimension_id',
                'dimension.reference as dimension_reference',
                'dimension.name as dimension_name',
                'stock.dimension2_id',
                'dimension2.reference as dimension2_reference',
                'dimension2.name as dimension2_name',
                'stock.purchase_cost',
                'stock.material_cost',
                'stock.labour_cost',
                'stock.overhead_cost',
                'stock.depreciation_method',
                'stock.depreciation_rate',
                'stock.depreciation_factor',
                'stock.depreciation_start',
                'stock.depreciation_date',
                'stock.fa_class_id',
            )
            ->selectRaw('CASE WHEN unit.decimals < 0 THEN NULL ELSE unit.decimals END as unit_decimals');

        $query->leftJoinSub(
            (new ItemInUseQuery)->builder(isset($filters['stock_id']) ? ['stock_id' => $filters['stock_id']] : []),
            'usage',
            'usage.stock_id',
            '=',
            'stock.stock_id'
        )->addSelect('usage.is_in_use');

        foreach (['sales', 'cogs', 'inventory', 'adjustment', 'wip'] as $kind) {
            $query->leftJoin("chart_master as {$kind}", "{$kind}.account_code", '=', "stock.{$kind}_account")
                ->addSelect("stock.{$kind}_account", "{$kind}.account_name as {$kind}_account_name");
        }

        if ($baseSalesTypeId == -1 || empty($baseSalesTypeId) || empty($homeCurrency)) {
            $query->selectRaw('NULL as normal_price');
        } else {
            $query->selectSub(
                DB::table('prices')
                    ->select('prices.price')
                    ->whereColumn('prices.stock_id', 'stock.stock_id')
                    ->where('prices.sales_type_id', $baseSalesTypeId)
                    ->where('prices.curr_abrev', $homeCurrency)
                    ->limit(1),
                'normal_price'
            );
        }

        if (isset($filters['stock_id'])) {
            $query->whereIn('stock.stock_id', (array) $filters['stock_id']);
        }

        if ($mode !== null) {
            $query->where('stock.mb_flag', match ($mode) {
                'items' => '!=',
                'fixed_assets' => '=',
            }, ItemSourceType::FixedAsset->value);
        }

        return $query;
    }
}
