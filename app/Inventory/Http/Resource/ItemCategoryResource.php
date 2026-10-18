<?php

namespace App\Inventory\Http\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category with what a new item in it starts from.
 */
class ItemCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->resource;

        return [
            'category_id' => (int) $category->category_id,
            'description' => $category->description,
            'tax_type_id' => (int) $category->dflt_tax_type,
            'units' => $category->dflt_units,
            'mb_flag' => $category->dflt_mb_flag,
            'sales_account' => $category->dflt_sales_act,
            'cogs_account' => $category->dflt_cogs_act,
            'inventory_account' => $category->dflt_inventory_act,
            'adjustment_account' => $category->dflt_adjustment_act,
            'wip_account' => $category->dflt_wip_act,
            'dimension' => DimensionResource::from($category->dflt_dim1, $category->dimension_reference, $category->dimension_name),
            'dimension2' => DimensionResource::from($category->dflt_dim2, $category->dimension2_reference, $category->dimension2_name),
            'no_sale' => (bool) $category->dflt_no_sale,
            'no_purchase' => (bool) $category->dflt_no_purchase,
        ];
    }
}
