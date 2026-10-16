<?php

namespace App\Inventory\Query;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The stock moves that count towards what is held: those dated on or before the day asked about, and not voided.
 */
class EffectiveStockMoveQuery
{
    public function builder(CarbonInterface $asOf): Builder
    {
        return DB::table('stock_moves')
            ->leftJoin('voided', function ($join) {
                $join->on('voided.type', '=', 'stock_moves.type')
                    ->on('voided.id', '=', 'stock_moves.trans_no');
            })
            ->whereNull('voided.id')
            ->where('stock_moves.tran_date', '<=', $asOf->toDateString());
    }
}
