<?php

namespace App\Inventory\Query;

use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Model\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Every item but the fixed assets, with its category and how much is on hand across every location.
 */
class ItemListQuery
{
    public function builder(): Builder
    {
        $onHand = (new EffectiveStockMoveQuery)->builder(Carbon::today())
            ->selectRaw('COALESCE(SUM(stock_moves.qty), 0)')
            ->whereColumn('stock_moves.stock_id', 'stock_master.stock_id');

        return Item::query()
            ->join('stock_category', 'stock_category.category_id', '=', 'stock_master.category_id')
            ->where('stock_master.mb_flag', '!=', ItemSourceType::FixedAsset->value)
            ->select([
                'stock_master.stock_id',
                'stock_master.description',
                'stock_master.category_id',
                'stock_master.mb_flag',
                'stock_master.units',
                'stock_master.inactive',
                'stock_category.description as category_name',
            ])
            ->selectSub($onHand, 'on_hand_qty');
    }
}
