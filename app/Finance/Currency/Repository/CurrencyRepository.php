<?php

namespace App\Finance\Currency\Repository;

use Illuminate\Support\Facades\DB;

class CurrencyRepository
{
    public function currencyExists(string $code, bool $includeInactive = false): bool
    {
        $query = DB::table('currencies')->where('curr_abrev', $code);

        if (! $includeInactive) {
            $query->where('inactive', false);
        }

        return $query->exists();
    }
}
