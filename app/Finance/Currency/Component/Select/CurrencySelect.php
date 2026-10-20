<?php

namespace App\Finance\Currency\Component\Select;

use App\Foundation\Component\Select\Contract\NarrowsOptionsContract;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * The currencies as a pick list by code. Inactive ones are left out unless `include_inactive=1`,
 * and `exclude_home=1` drops the home currency, as legacy offers a list of the currencies a rate
 * can be set for.
 */
final class CurrencySelect implements NarrowsOptionsContract, SelectDefinitionContract
{
    public function query(): QueryBuilder
    {
        return DB::table('currencies as currency')
            ->select([
                'currency.curr_abrev as value',
                'currency.curr_abrev as label',
            ])
            ->selectRaw('CASE WHEN currency.inactive THEN ? ELSE currency.currency END as description', [__('shared.record.inactive')])
            ->orderBy('currency.curr_abrev');
    }

    public function filterRules(): array
    {
        return [
            'include_inactive' => ['nullable', 'boolean'],
            'exclude_home' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A held currency passes whatever the filters say, so narrowing the list never removes the
     * currency a form already holds.
     */
    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        $query->where(function (EloquentBuilder|QueryBuilder $rows) use ($state) {
            $rows->where(function (EloquentBuilder|QueryBuilder $query) use ($state) {
                $query->whereRaw('1 = 1');

                if (empty($state->filter('include_inactive'))) {
                    $query->where('currency.inactive', 0);
                }

                if (! empty($state->filter('exclude_home'))) {
                    $query->where('currency.curr_abrev', '!=', (string) settings()->homeCurrency());
                }
            });

            if ($state->selected !== []) {
                $rows->orWhereIn('currency.curr_abrev', $state->selected);
            }
        });
    }

    public function searchColumns(): array
    {
        return ['currency.curr_abrev', 'currency.currency'];
    }

    public function valueColumn(): string
    {
        return 'currency.curr_abrev';
    }

    public static function routeName(): string
    {
        return 'finance.currencies.options';
    }
}
