<?php

namespace App\Inventory\Component\Select;

use App\Foundation\Component\Select\Contract\NarrowsOptionsContract;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\ValueObject\SelectState;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Model\ItemCategory;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * The item categories as a pick list: those that file ordinary items unless `fixed_assets_only=1` asks
 * for those that file fixed assets, and the inactive ones left out unless `include_inactive=1` asks
 * for them.
 */
final class ItemCategorySelect implements NarrowsOptionsContract, SelectDefinitionContract
{
    public function query(): EloquentBuilder
    {
        return ItemCategory::query()
            ->select(
                'stock_category.category_id as value',
                'stock_category.description as label'
            )
            ->selectRaw('CASE WHEN stock_category.inactive THEN ? END as description', [__('shared.record.inactive')])
            ->orderBy('stock_category.description');
    }

    public function filterRules(): array
    {
        return [
            'fixed_assets_only' => ['nullable', 'boolean'],
            'include_inactive' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A held category passes whatever the filters say, so narrowing the list never closes the
     * category that is open.
     */
    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        $query->where(function (EloquentBuilder|QueryBuilder $rows) use ($state) {
            $rows->where(function (EloquentBuilder|QueryBuilder $query) use ($state) {
                $query->whereRaw('1 = 1');

                if (! empty($state->filter('fixed_assets_only'))) {
                    $query->where('stock_category.dflt_mb_flag', '=', ItemSourceType::FixedAsset->value);
                } else {
                    $query->where('stock_category.dflt_mb_flag', '!=', ItemSourceType::FixedAsset->value);
                }

                if (empty($state->filter('include_inactive'))) {
                    $query->where('stock_category.inactive', 0);
                }
            });

            if ($state->selected !== []) {
                $rows->orWhereIn('stock_category.category_id', $state->selected);
            }
        });
    }

    public function searchColumns(): array
    {
        return ['stock_category.description'];
    }

    public function valueColumn(): string
    {
        return 'stock_category.category_id';
    }

    public static function routeName(): string
    {
        return 'inventory.categories.options';
    }
}
