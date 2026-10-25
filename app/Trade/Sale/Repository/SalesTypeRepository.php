<?php

namespace App\Trade\Sale\Repository;

use App\Foundation\Shared\Exception\ResourceNotFoundException;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class SalesTypeRepository
{
    public function getSalesTypeFactor(int $salesTypeId): BigDecimal
    {
        $factor = DB::table('sales_types')->where('id', $salesTypeId)->value('factor')
            ?? throw ResourceNotFoundException::for('SalesType', $salesTypeId);

        return BigDecimal::of($factor);
    }
}
