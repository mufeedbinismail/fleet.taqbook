<?php

namespace App\Inventory\Query;

use App\Inventory\Enum\ItemSourceType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * How much of each manufactured item the released, unclosed work orders have yet to make, by the place they make it at.
 */
class OnWorkOrderQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null, location_code?: string|null}  $filters
     */
    public function builder(array $filters = []): Builder
    {
        $query = DB::table('workorders')
            ->where('workorders.released', 1)
            ->where('workorders.closed', 0)
            ->whereExists(DB::table('stock_master as manufactured')
                ->whereColumn('manufactured.stock_id', 'workorders.stock_id')
                ->where('manufactured.mb_flag', ItemSourceType::Manufactured->value))
            ->select('workorders.stock_id', 'workorders.loc_code')
            ->selectRaw('SUM(workorders.units_reqd - workorders.units_issued) as quantity')
            ->groupBy('workorders.stock_id', 'workorders.loc_code');

        if (isset($filters['stock_id'])) {
            $query->whereIn('workorders.stock_id', (array) $filters['stock_id']);
        }

        if (isset($filters['location_code'])) {
            $query->where('workorders.loc_code', $filters['location_code']);
        }

        return $query;
    }
}
