<?php

namespace App\Trade\Marketplace\Query\Marketplace;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MarketplaceQuery
{
    public function builder(int|string $id): Builder
    {
        return DB::table('marketplaces as m')
            ->join('chart_master as a', 'm.provision_account', '=', 'a.account_code')
            ->leftJoin('suppliers as s', 'm.supplier_id', '=', 's.supplier_id')
            ->join('chart_master as payable', 's.payable_account', '=', 'payable.account_code')
            ->where('m.id', $id)
            ->select(
                DB::raw('m.*'),
                'a.account_name as provision_account_name',
                's.supp_name as supplier_name',
                's.tax_group_id',
                's.tax_included',
                's.payable_account',
                'payable.account_name as payable_account_name'
            );
    }
}
