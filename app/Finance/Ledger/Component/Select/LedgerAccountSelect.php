<?php

namespace App\Finance\Ledger\Component\Select;

use App\Finance\Ledger\Enum\ChartClass;
use App\Foundation\Component\Select\Contract\NarrowsOptionsContract;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The general-ledger accounts as a pick list grouped by account type, with the inactive ones left
 * out unless `include_inactive=1` asks for them. `skip_bank_accounts=1`, `type_id` and `class` (a
 * list of `ChartClass` values, matched to `chart_class.ctype`) narrow it as the legacy account list's
 * arguments did.
 */
final class LedgerAccountSelect implements NarrowsOptionsContract, SelectDefinitionContract
{
    public function query(): QueryBuilder
    {
        return DB::table('chart_master as chart')
            ->join('chart_types as type', 'type.id', '=', 'chart.account_type')
            ->join('chart_class as class', 'class.cid', '=', 'type.class_id')
            ->select(['chart.account_code as value'])
            ->selectRaw("CONCAT_WS(' - ', chart.account_code, chart.account_name) as label")
            ->selectRaw("CONCAT_WS(' · ', type.name, CASE WHEN chart.inactive THEN ? END) as description", [__('shared.record.inactive')])
            ->selectRaw('type.name as `group`')
            ->orderBy('class.cid')
            ->orderBy('type.id')
            ->orderBy('chart.account_code');
    }

    public function filterRules(): array
    {
        return [
            'skip_bank_accounts' => ['nullable', 'boolean'],
            'include_inactive' => ['nullable', 'boolean'],
            'type_id' => ['nullable', 'string', 'max:10'],
            'class' => ['nullable', 'array'],
            'class.*' => [Rule::enum(ChartClass::class)],
        ];
    }

    /**
     * A held account passes whatever the filters say, so narrowing the list never closes the
     * account that is open.
     */
    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        $query->where(function (EloquentBuilder|QueryBuilder $rows) use ($state) {
            $rows->where(function (EloquentBuilder|QueryBuilder $query) use ($state) {
                // Without a condition of its own the group would be dropped, leaving the held
                // values as the only rows the list offers.
                $query->whereRaw('1 = 1');

                if (! empty($state->filter('skip_bank_accounts'))) {
                    $query->whereNotExists(function (QueryBuilder $bank) {
                        $bank->selectRaw('1')
                            ->from('bank_accounts')
                            ->whereColumn('bank_accounts.account_code', 'chart.account_code');
                    });
                }

                if (empty($state->filter('include_inactive'))) {
                    $query->where('chart.inactive', 0);
                }

                if (! empty($typeId = $state->filter('type_id'))) {
                    $query->where('chart.account_type', $typeId);
                }

                if (! empty($classes = $state->filter('class'))) {
                    $query->whereIn('class.ctype', array_map(fn ($class) => ChartClass::from($class)->value, $classes));
                }
            });

            if ($state->selected !== []) {
                $rows->orWhereIn('chart.account_code', $state->selected);
            }
        });
    }

    public function searchColumns(): array
    {
        return ['chart.account_code', 'chart.account_name', 'type.name'];
    }

    public function valueColumn(): string
    {
        return 'chart.account_code';
    }

    public static function routeName(): string
    {
        return 'finance.accounts.options';
    }
}
