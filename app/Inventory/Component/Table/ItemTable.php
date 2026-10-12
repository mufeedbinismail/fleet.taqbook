<?php

namespace App\Inventory\Component\Table;

use App\Foundation\Component\Select\Control\SelectControl;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Contract\TableDefinitionContract;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Filter\ExactFilter;
use App\Foundation\Component\Table\Filter\SwitchFilter;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\FilterDefinition;
use App\Foundation\Component\Table\ValueObject\Table;
use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Enum\ItemSourceType;
use App\Inventory\Model\Item;
use App\Inventory\Query\ItemListQuery;

/**
 * The items of the items page, each with what is on hand of it, narrowed by category, type and
 * whether the inactive ones are wanted.
 */
final class ItemTable implements TableDefinitionContract
{
    public function __construct(
        private readonly OptionService $optionService,
        private readonly ItemCategorySelect $categorySelect,
        private readonly TableBuilder $builder,
    ) {}

    public static function routeName(): string
    {
        return 'inventory.items.list';
    }

    public function table(): Table
    {
        return $this->builder
            ->of((new ItemListQuery)->builder())
            ->name('items')
            ->searchable('stock_master.stock_id', 'stock_master.description', 'stock_category.description')
            ->filterable(
                new FilterDefinition('show_inactive', __('inventory.item.filter.inactive'), new SwitchFilter('stock_master.inactive', flipped: true)),
                new FilterDefinition(
                    'category_id',
                    __('inventory.item.filter.category'),
                    new ExactFilter('stock_master.category_id', SelectControl::from($this->optionService->channel($this->categorySelect))),
                ),
                new FilterDefinition(
                    'item_type',
                    __('inventory.item.filter.type'),
                    new ExactFilter('stock_master.mb_flag', SelectControl::simple(collect(ItemSourceType::labels())->except(ItemSourceType::FixedAsset->value)->toArray())),
                ),
            )
            ->defaultSort('stock_master.description')
            ->defaultFilters(['show_inactive' => false])
            ->perPage(25, max: 100)
            ->definitions(
                new ColumnDefinition('description', __('inventory.item.column.item'), sortable: 'stock_master.description'),
                new ColumnDefinition('on_hand', __('inventory.item.column.on_hand'), sortable: 'on_hand_qty', width: '6rem', align: 'right'),
                new ColumnDefinition('stock_id', visible: false),
                new ColumnDefinition('category_name', visible: false),
                new ColumnDefinition('inactive', dataType: DataType::Boolean, visible: false, exportable: false),
            )
            ->map($this->row(...))
            ->definition();
    }

    /**
     * A service has no stock to count, so its row carries no figure rather than a zero that would
     * read as having run out.
     *
     * @return array<string, mixed>
     */
    private function row(Item $record): array
    {
        $settings = user_settings();

        return [
            'stock_id' => $record->stock_id,
            'description' => $record->description,
            'category_name' => $record->category_name,
            'inactive' => $record->inactive,
            'on_hand' => $record->mb_flag === ItemSourceType::Service->value ? null : number_format(
                (float) $record->on_hand_qty,
                $settings->quantityDecimals(),
                $settings->decimalSepIdx()->label(),
                $settings->thousandSepIdx()->label(),
            ),
        ];
    }
}
