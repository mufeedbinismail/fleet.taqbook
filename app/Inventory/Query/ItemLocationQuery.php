<?php

namespace App\Inventory\Query;

use App\Inventory\Enum\ItemSourceType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Every item at every place, with the level at which the place is to reorder it; fixed-asset places among them.
 */
class ItemLocationQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null, location_code?: string|null}  $filters
     * @param  'items'|'fixed_assets'|null  $mode  `items` for those that are bought, made or sold at the places that hold them, `fixed_assets` for the fixed assets at theirs, none for both
     */
    public function builder(array $filters = [], ?string $mode = null): Builder
    {
        $query = DB::table('stock_master')
            ->crossJoin('locations')
            ->leftJoin('item_units', 'item_units.abbr', '=', 'stock_master.units')
            ->leftJoin('loc_stock', function ($join) {
                $join->on('loc_stock.stock_id', '=', 'stock_master.stock_id')
                    ->on('loc_stock.loc_code', '=', 'locations.loc_code');
            })
            ->select(
                'stock_master.stock_id',
                'locations.loc_code',
                'locations.location_name',
                'locations.inactive as location_inactive',
                'locations.fixed_asset as location_fixed_asset',
                'stock_master.mb_flag'
            )
            ->selectRaw('CASE WHEN item_units.decimals < 0 THEN NULL ELSE item_units.decimals END as unit_decimals')
            ->selectRaw('COALESCE(loc_stock.reorder_level, 0) as reorder_level')
            ->orderBy('stock_master.stock_id')
            ->orderBy('locations.loc_code');

        if (isset($filters['stock_id'])) {
            $query->whereIn('stock_master.stock_id', (array) $filters['stock_id']);
        }

        if (isset($filters['location_code'])) {
            $query->where('locations.loc_code', $filters['location_code']);
        }

        if ($mode !== null) {
            if ($mode == 'fixed_assets') {
                $query
                    ->where('stock_master.mb_flag', ItemSourceType::FixedAsset->value)
                    ->where('locations.fixed_asset', 1);
            } elseif ($mode == 'items') {
                $query
                    ->where('stock_master.mb_flag', '!=', ItemSourceType::FixedAsset->value)
                    ->where('locations.fixed_asset', 0);
            } else {
                throw new \InvalidArgumentException('Unknown mode: '.$mode);
            }
        }

        return $query;
    }
}
