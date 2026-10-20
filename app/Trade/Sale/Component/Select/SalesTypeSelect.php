<?php

namespace App\Trade\Sale\Component\Select;

use App\Foundation\Component\Select\Contract\NarrowsOptionsContract;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * The sales types as a pick list. Inactive ones are left out unless `include_inactive=1`.
 */
final class SalesTypeSelect implements NarrowsOptionsContract, SelectDefinitionContract
{
    public function query(): QueryBuilder
    {
        return DB::table('sales_types as type')
            ->select([
                'type.id as value',
                'type.sales_type as label',
            ])
            ->selectRaw('CASE WHEN type.inactive THEN ? END as description', [__('shared.record.inactive')])
            ->orderBy('type.sales_type');
    }

    public function filterRules(): array
    {
        return [
            'include_inactive' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A held sales type passes whatever the filters say, so narrowing the list never removes the
     * type a form already holds.
     */
    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        $query->where(function (EloquentBuilder|QueryBuilder $rows) use ($state) {
            $rows->where(function (EloquentBuilder|QueryBuilder $query) use ($state) {
                $query->whereRaw('1 = 1');

                if (empty($state->filter('include_inactive'))) {
                    $query->where('type.inactive', 0);
                }
            });

            if ($state->selected !== []) {
                $rows->orWhereIn('type.id', $state->selected);
            }
        });
    }

    public function searchColumns(): array
    {
        return ['type.sales_type'];
    }

    public function valueColumn(): string
    {
        return 'type.id';
    }

    public static function routeName(): string
    {
        return 'sale.sales-types.options';
    }
}
