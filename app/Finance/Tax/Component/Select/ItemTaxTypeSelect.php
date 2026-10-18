<?php

namespace App\Finance\Tax\Component\Select;

use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * The item tax types as a pick list: all of them, the inactive ones marked and not pickable, so an
 * item that still holds one is shown it.
 */
final class ItemTaxTypeSelect implements SelectDefinitionContract
{
    public function query(): QueryBuilder
    {
        return DB::table('item_tax_types as tax_type')
            ->select([
                'tax_type.id as value',
                'tax_type.name as label',
            ])
            ->selectRaw('CASE WHEN tax_type.inactive THEN ? END as description', [__('shared.record.inactive')])
            ->selectRaw('tax_type.inactive as disabled')
            ->orderBy('tax_type.name');
    }

    public function searchColumns(): array
    {
        return ['tax_type.name'];
    }

    public function valueColumn(): string
    {
        return 'tax_type.id';
    }

    public static function routeName(): string
    {
        return 'finance.item-tax-types.options';
    }
}
