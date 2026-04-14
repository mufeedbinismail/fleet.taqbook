<?php

namespace App\Finance\Tax\Query\TaxGroup;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TaxGroupLinesQuery
{
    public function builder(int $taxGroupId = null, bool $shippingOnly = false): Builder
    {
        return DB::query()
            ->select(
                't.id as tax_type_id',
                settings('suppress_tax_rates')
                    ? 't.name as tax_type_name'
                    : DB::raw("CONCAT(t.name, ' (', TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(t.rate AS VARCHAR(15)))), '%)') as tax_type_name"),
                't.sales_gl_code',
                't.purchasing_gl_code',
                't.purchasing_provision_gl_code',
                DB::raw('IF(g.tax_type_id, t.rate, NULL) as rate'),
                'g.tax_shipping'
            )
            ->from('tax_types as t')
            ->leftJoin('tax_group_items as g', 't.id', '=', 'g.tax_type_id')
            ->where('t.inactive', false)
            ->where('g.tax_group_id', $taxGroupId ?: DB::table('tax_groups')->selectRaw('MIN(id) as min_id'))
            ->when($shippingOnly, function (Builder $query) {
                return $query->where('g.tax_shipping', 1);
            });
    }
}