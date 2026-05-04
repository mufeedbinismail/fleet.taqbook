<?php

namespace App\Inventory\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ItemInfoQuery
{
    public function builder(string|array $stockId): Builder
    {
        if (is_string($stockId)) {
            $stockId = [$stockId];
        }

        return DB::table('stock_master as stock')
            ->join('item_units as unit', 'stock.units', '=', 'unit.abbr')
            ->whereIn('stock.stock_id', $stockId)
            ->select(
                'stock.stock_id',
                'stock.category_id',
                'stock.tax_type_id',
                'stock.description',
                'stock.long_description',
                'stock.units',
                'stock.mb_flag',
                'stock.sales_account',
                'stock.cogs_account',
                'stock.inventory_account',
                'stock.adjustment_account',
                'stock.wip_account',
                'stock.dimension_id',
                'stock.dimension2_id',
                'stock.purchase_cost',
                'stock.material_cost',
                'stock.labour_cost',
                'stock.overhead_cost',
                'stock.inactive',
                'stock.no_sale',
                'stock.no_purchase',
                'stock.editable',
                'stock.depreciation_method',
                'stock.depreciation_rate',
                'stock.depreciation_factor',
                'stock.depreciation_start',
                'stock.depreciation_date',
                'stock.fa_class_id',
            )
            ->selectRaw('if(unit.decimals = -1, ?, unit.decimals) as unit_decimals', [user_settings()->quantityDecimals()]);
    }
}