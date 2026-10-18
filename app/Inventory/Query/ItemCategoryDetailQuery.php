<?php

namespace App\Inventory\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Every category with what a new item in it starts from, and the names of the dimensions it starts them with.
 */
class ItemCategoryDetailQuery
{
    /**
     * @param  array{category_id?: int|list<int>|null}  $filters  the categories asked for, a missing or null one not narrowing at all
     */
    public function builder(array $filters = []): Builder
    {
        $query = DB::table('stock_category as category')
            ->leftJoin('dimensions as dimension', 'dimension.id', '=', 'category.dflt_dim1')
            ->leftJoin('dimensions as dimension2', 'dimension2.id', '=', 'category.dflt_dim2')
            ->select(
                'category.category_id',
                'category.description',
                'category.dflt_tax_type',
                'category.dflt_units',
                'category.dflt_mb_flag',
                'category.dflt_sales_act',
                'category.dflt_cogs_act',
                'category.dflt_inventory_act',
                'category.dflt_adjustment_act',
                'category.dflt_wip_act',
                'category.dflt_dim1',
                'dimension.reference as dimension_reference',
                'dimension.name as dimension_name',
                'category.dflt_dim2',
                'dimension2.reference as dimension2_reference',
                'dimension2.name as dimension2_name',
                'category.dflt_no_sale',
                'category.dflt_no_purchase',
            );

        if (isset($filters['category_id'])) {
            $query->whereIn('category.category_id', (array) $filters['category_id']);
        }

        return $query;
    }
}
