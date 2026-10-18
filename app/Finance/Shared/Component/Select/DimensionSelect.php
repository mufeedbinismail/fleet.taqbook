<?php

namespace App\Finance\Shared\Component\Select;

use App\Foundation\Component\Select\Contract\NarrowsOptionsContract;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * The open dimensions as a pick list, `reference - name`. `level=1|2` keeps those of one dimension
 * type, as legacy offers an item its first and its second dimension; `include_closed=1` adds the
 * closed ones.
 */
final class DimensionSelect implements NarrowsOptionsContract, SelectDefinitionContract
{
    public function query(): QueryBuilder
    {
        return DB::table('dimensions as dimension')
            ->select(['dimension.id as value'])
            ->selectRaw("CONCAT_WS(' - ', dimension.reference, dimension.name) as label")
            ->orderBy('dimension.reference');
    }

    public function filterRules(): array
    {
        return [
            'level' => ['nullable', 'integer', 'in:1,2'],
            'include_closed' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A held dimension passes whatever the filters say, so narrowing the list never closes the
     * dimension that is open.
     */
    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        $query->where(function (EloquentBuilder|QueryBuilder $rows) use ($state) {
            $rows->where(function (EloquentBuilder|QueryBuilder $query) use ($state) {
                $query->whereRaw('1 = 1');

                if ($state->filter('level') !== null) {
                    $query->where('dimension.type_', (int) $state->filter('level'));
                }

                if (empty($state->filter('include_closed'))) {
                    $query->where('dimension.closed', 0);
                }
            });

            if ($state->selected !== []) {
                $rows->orWhereIn('dimension.id', $state->selected);
            }
        });
    }

    public function searchColumns(): array
    {
        return ['dimension.reference', 'dimension.name'];
    }

    public function valueColumn(): string
    {
        return 'dimension.id';
    }

    public static function routeName(): string
    {
        return 'finance.dimensions.options';
    }
}
