<?php

namespace App\Finance\Ledger\Repository;

use Illuminate\Support\Facades\DB;

class LedgerAccountRepository
{
    public function accountExists(string $accountCode): bool
    {
        return DB::table('chart_master')->where('account_code', $accountCode)->exists();
    }
}
