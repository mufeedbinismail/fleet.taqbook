<?php

namespace App\Inventory\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * What is held, wanted and coming of each item at each place, fixed-asset places among them, as the legacy item status
 * page works it out. A service has the same columns, of which only the demand means anything.
 *
 * It is the place each item is kept at, joined to the queries that count each thing; a count asked for joins only the
 * queries it is worked out from.
 */
class ItemStatusQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null, location_code?: string|null}  $filters
     * @param  string|list<string>  $select  `*` for every count, or the ones to work out from `on_hand`, `demand`, `on_order` and `available`, which decide the queries joined for them; every row has the place's own columns besides
     * @param  'items'|'fixed_assets'|null  $mode  `items` for those that are bought, made or sold at the places that hold them, `fixed_assets` for the fixed assets at theirs, none for both
     */
    public function builder(array $filters = [], array|string $select = '*', ?string $mode = 'items'): Builder
    {
        $demand = [
            'expression' => 'COALESCE(sales.quantity, 0) + COALESCE(assembly.quantity, 0)',
            'depends' => ['sales', 'assembly'],
        ];

        // Each count, the expression that works it out, and the queries it reads.
        $columns = [
            'on_hand' => [
                'expression' => 'COALESCE(held.quantity, 0)',
                'depends' => ['held'],
            ],
            'demand' => $demand,
            'on_order' => [
                'expression' => 'COALESCE(purchased.quantity, 0) + COALESCE(made.quantity, 0)',
                'depends' => ['purchased', 'made'],
            ],
            'available' => [
                'expression' => "COALESCE(held.quantity, 0) - ({$demand['expression']})",
                'depends' => ['held', ...$demand['depends']],
            ],
        ];

        // In the order they are joined.
        $counts = [
            'held' => new StockOnHandQuery,
            'sales' => new SalesDemandQuery,
            'assembly' => new AssemblyDemandQuery,
            'purchased' => new OnPurchaseOrderQuery,
            'made' => new OnWorkOrderQuery,
        ];

        $wanted = $select === '*'
            ? array_keys($columns)
            : (is_array($select) ? $select : [$select]);

        if ($unknown = array_diff($wanted, array_keys($columns))) {
            throw new InvalidArgumentException('Unknown item status column passed in as $select: '.implode(', ', $unknown));
        }

        $needed = array_merge(...array_map(fn ($name) => $columns[$name]['depends'], $wanted));

        $query = DB::query()
            ->fromSub((new ItemLocationQuery)->builder($filters, $mode), 'place')
            ->select(
                'place.stock_id',
                'place.loc_code',
                'place.location_name',
                'place.location_inactive',
                'place.location_fixed_asset',
                'place.mb_flag',
                'place.unit_decimals',
                'place.reorder_level',
            )
            ->orderBy('place.stock_id')
            ->orderBy('place.loc_code');

        foreach ($counts as $alias => $count) {
            if (in_array($alias, $needed, true)) {
                $query->leftJoinSub($count->builder($filters), $alias, fn ($join) => $join
                    ->on("{$alias}.stock_id", '=', 'place.stock_id')
                    ->on("{$alias}.loc_code", '=', 'place.loc_code'));
            }
        }

        foreach ($wanted as $name) {
            $query->selectRaw("{$columns[$name]['expression']} as {$name}");
        }

        return $query;
    }
}
