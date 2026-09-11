<?php

namespace App\Trade\Sale\Repository;

use Illuminate\Support\Facades\DB;

class SalesPointRepository
{
    /**
     * Every point of sale there is, inactive ones included. Hiding those is a decision for whoever
     * asked, not a second query.
     *
     * @return list<array{id: int, name: string, inactive: bool}>
     */
    public function all(): array
    {
        return DB::table('sales_pos')
            ->orderBy('pos_name')
            ->get(['id', 'pos_name', 'inactive'])
            ->map(fn (object $row) => [
                'id' => (int) $row->id,
                'name' => $row->pos_name,
                'inactive' => (bool) $row->inactive,
            ])
            ->all();
    }
}
