<?php

namespace App\Inventory\Query;

use App\Foundation\Shared\Enum\SystemType;
use App\Inventory\Enum\ItemSourceType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * How much of each component the manufactured items still to be sent will call for, by place. Each open sales line of a
 * manufactured item is broken into its components, and those into theirs, but only so far as the stock of the item being
 * broken down falls short of what is needed.
 */
class AssemblyDemandQuery
{
    /**
     * How many levels of a bill of materials are followed down from an item sold, counting the item's own.
     */
    private const BOM_DEPTH = 11;

    /**
     * @param  array{stock_id?: string|list<string>|null, location_code?: string|null}  $filters  the component, and the place
     */
    public function builder(array $filters = []): Builder
    {
        $salesOrder = SystemType::SalesOrder->value;
        $manufactured = ItemSourceType::Manufactured->value;
        $depth = self::BOM_DEPTH;

        // What is held today, counted as it is everywhere else; its columns are the ones the explosion reads.
        $held = (new StockOnHandQuery)->builder();

        $explosion = <<<SQL
            WITH RECURSIVE
            held AS ({$held->toSql()}),
            sold AS (
                SELECT line.stk_code AS stock_id, sorder.from_stk_loc AS loc_code, SUM(line.quantity - line.qty_sent) AS quantity
                FROM sales_order_details line
                JOIN sales_orders sorder ON sorder.order_no = line.order_no AND sorder.trans_type = line.trans_type
                JOIN stock_master item ON item.stock_id = line.stk_code
                WHERE sorder.trans_type = {$salesOrder} AND line.quantity - line.qty_sent > 0 AND item.mb_flag = '{$manufactured}'
                GROUP BY line.stk_code, sorder.from_stk_loc
            ),
            explosion AS (
                SELECT bom.component, bom.loc_code, sold.quantity * bom.quantity AS quantity, 1 AS depth
                FROM sold
                LEFT JOIN held ON held.stock_id = sold.stock_id AND held.loc_code = sold.loc_code
                JOIN bom ON bom.parent = sold.stock_id AND bom.loc_code = sold.loc_code
                WHERE sold.quantity > COALESCE(held.quantity, 0)
                UNION ALL
                SELECT bom.component, bom.loc_code, explosion.quantity * bom.quantity, explosion.depth + 1
                FROM explosion
                LEFT JOIN held ON held.stock_id = explosion.component AND held.loc_code = explosion.loc_code
                JOIN bom ON bom.parent = explosion.component AND bom.loc_code = explosion.loc_code
                WHERE explosion.quantity > COALESCE(held.quantity, 0) AND explosion.depth < {$depth}
            )
            SELECT component AS stock_id, loc_code, SUM(quantity) AS quantity FROM explosion GROUP BY component, loc_code
            SQL;

        $query = DB::query()
            ->fromRaw('('.$explosion.') as assembly', $held->getBindings())
            ->select('assembly.stock_id', 'assembly.loc_code', 'assembly.quantity');

        if (isset($filters['stock_id'])) {
            $query->whereIn('assembly.stock_id', (array) $filters['stock_id']);
        }

        if (isset($filters['location_code'])) {
            $query->where('assembly.loc_code', $filters['location_code']);
        }

        return $query;
    }
}
