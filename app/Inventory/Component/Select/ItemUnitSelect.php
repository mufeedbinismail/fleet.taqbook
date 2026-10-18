<?php

namespace App\Inventory\Component\Select;

use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * The units of measure as a pick list. An inactive unit is listed but cannot be picked, so an item that
 * still holds one shows it while no other item can take it up.
 */
final class ItemUnitSelect implements SelectDefinitionContract
{
    public function query(): QueryBuilder
    {
        return DB::table('item_units as unit')
            ->select([
                'unit.abbr as value',
                'unit.name as label',
                'unit.inactive as disabled',
            ])
            ->selectRaw('CASE WHEN unit.inactive THEN ? END as description', [__('shared.record.inactive')])
            ->orderBy('unit.name');
    }

    public function searchColumns(): array
    {
        return ['unit.name', 'unit.abbr'];
    }

    public function valueColumn(): string
    {
        return 'unit.abbr';
    }

    public static function routeName(): string
    {
        return 'inventory.item-units.options';
    }
}
