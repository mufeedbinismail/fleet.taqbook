<?php

namespace App\Finance\Ledger\Query;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TransactionAttributionExistsQuery
{
    /**
     * @param  int|Expression  $user  an expression is inlined rather than bound, so a column here
     *                                reads from an enclosing query
     */
    public function builder(int|Expression $user): Builder
    {
        // Shaped to survive in a select list: one column, one row, and no name of its own.
        return DB::table('audit_trail')
            ->selectRaw('1')
            ->where('audit_trail.user', $user)
            ->limit(1);
    }
}
