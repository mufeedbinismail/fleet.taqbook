<?php

namespace App\Inventory\Component\Select;

use App\Foundation\Component\Select\Contract\NarrowsOptionsContract;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\ValueObject\SelectState;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Model\Item;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

/**
 * The items as a pick list described by their category, named with their code where the person
 * asked to see codes. Ordinary items unless `fixed_assets_only=1` asks for the fixed assets, and
 * the inactive ones left out unless `include_inactive=1` asks for them.
 */
final class ItemSelect implements NarrowsOptionsContract, SelectDefinitionContract
{
    public function query(): EloquentBuilder
    {
        $codes = user_settings()->showItemCodesAlso();

        return Item::query()
            ->join('stock_category', 'stock_category.category_id', '=', 'stock_master.category_id')
            ->select(['stock_master.stock_id as value'])
            ->selectRaw("CONCAT_WS(' - ', ".($codes ? 'stock_master.stock_id' : 'NULL').', stock_master.description) as label')
            ->selectRaw("CONCAT_WS(' · ', stock_category.description, CASE WHEN stock_master.inactive THEN ? END) as description", [__('shared.record.inactive')])
            ->orderBy('stock_master.description');
    }

    public function filterRules(): array
    {
        return [
            'fixed_assets_only' => ['nullable', 'boolean'],
            'include_inactive' => ['nullable', 'boolean'],
            'marketplace_items_only' => ['nullable', 'boolean'],
            'purchasable_only' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'integer'],
            'item_source_type' => ['nullable', Rule::enum(ItemSourceType::class)],
        ];
    }

    /**
     * A held item passes whatever the filters say, so narrowing the list never closes the item that is open.
     */
    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        $query->where(function (EloquentBuilder|QueryBuilder $rows) use ($state) {
            $rows->where(function (EloquentBuilder|QueryBuilder $query) use ($state) {
                if (! empty($state->filter('fixed_assets_only'))) {
                    $query->where('stock_master.mb_flag', '=', ItemSourceType::FixedAsset->value);
                } else {
                    $query->where('stock_master.mb_flag', '!=', ItemSourceType::FixedAsset->value);
                }

                if (empty($state->filter('include_inactive'))) {
                    $query->where('stock_master.inactive', 0);
                }

                // An empty list of marketplace items offers none, not every item.
                if (! empty($state->filter('marketplace_items_only'))) {
                    $query->whereIn('stock_master.stock_id', settings()->marketplaceExpenseItems());
                }

                if (! empty($state->filter('purchasable_only'))) {
                    $query->where('stock_master.no_purchase', 0);
                }

                if (! empty($category = $state->filter('category_id'))) {
                    $query->where('stock_master.category_id', $category);
                }

                if (! empty($source = $state->enum('item_source_type', ItemSourceType::class))) {
                    $query->where('stock_master.mb_flag', $source->value);
                }
            });

            if ($state->selected !== []) {
                $rows->orWhereIn('stock_master.stock_id', $state->selected);
            }
        });
    }

    public function searchColumns(): array
    {
        return ['stock_master.stock_id', 'stock_master.description', 'stock_category.description'];
    }

    public function valueColumn(): string
    {
        return 'stock_master.stock_id';
    }

    public static function routeName(): string
    {
        return 'inventory.items.options';
    }
}
