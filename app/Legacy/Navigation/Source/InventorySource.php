<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Constant\Permission;
use App\Legacy\Navigation\Enum\Query;
use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Builder\SectionBuilder;
use App\Navigation\Constant\Area;
use App\Navigation\Constant\Section;
use App\Navigation\Enum\Category;
use App\Navigation\Enum\Column;

class InventorySource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::INVENTORY, $this->label('&Items and Inventory'), function (AreaBuilder $inventory) {
            $inventory->icon('icon-inventory')
                ->help('Items and Inventory')
                ->sort(40)
                ->target($this->script('admin/dashboard.php', ['sel_app' => 'stock']));

            $inventory->section(Section::INVENTORY_TRANSACTION, $this->label('Transactions'), function (SectionBuilder $section) {
                $section->page('transfer.create', $this->label('Inventory Location &Transfers'))
                    ->target($this->script('inventory/transfers.php', ['NewTransfer' => '1']))
                    ->permission(Permission::CREATE_INVENTORY_TRANSFER)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('adjustment.create', $this->label('Inventory &Adjustments'))
                    ->target($this->script('inventory/adjustments.php', ['NewAdjustment' => '1']))
                    ->permission(Permission::CREATE_ADJUSTMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(20);
            })->sort(10);

            $inventory->section(Section::INVENTORY_INQUIRY, $this->label('Inquiries and Reports'), function (SectionBuilder $section) {
                $section->page('movement.inquire', $this->label('Inventory Item &Movements'))
                    ->target($this->script('inventory/inquiry/stock_movements.php'))
                    ->permission(Permission::VIEW_INVENTORY_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('status.inquire', $this->label('Inventory Item &Status'))
                    ->target($this->script('inventory/inquiry/stock_status.php'))
                    ->permission(Permission::VIEW_INVENTORY_STATUS)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('transaction.report', $this->label('Inventory &Reports'))
                    ->target($this->reports(2))
                    ->permission(Permission::VIEW_INVENTORY_TRANSACTION)
                    ->category(Category::Report)
                    ->place(Column::Right)
                    ->sort(30);
            })->sort(20);

            $inventory->section(Section::INVENTORY_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('item.manage', $this->label('&Items'))
                    ->target($this->script('inventory/manage/items.php'))
                    ->permission(Permission::MANAGE_INVENTORY_ITEM)
                    ->category(Category::Entry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('foreign-code.manage', $this->label('&Foreign Item Codes'))
                    ->target($this->script('inventory/manage/item_codes.php'))
                    ->permission(Permission::MANAGE_FOREIGN_CODE)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('kit.manage', $this->label('Sales &Kits'))
                    ->target($this->script('inventory/manage/sales_kits.php'))
                    ->permission(Permission::MANAGE_KIT)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('category.manage', $this->label('Item &Categories'))
                    ->target($this->script('inventory/manage/item_categories.php'))
                    ->permission(Permission::MANAGE_INVENTORY_CATEGORY)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('location.manage', $this->label('Inventory &Locations'))
                    ->target($this->script('inventory/manage/locations.php'))
                    ->permission(Permission::MANAGE_INVENTORY_LOCATION)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(50);

                $section->page('unit.manage', $this->label('&Units of Measure'))
                    ->target($this->script('inventory/manage/item_units.php'))
                    ->permission(Permission::MANAGE_INVENTORY_UNIT)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(60);

                $section->page('reorder.report', $this->label('&Reorder Levels'))
                    ->target($this->script('inventory/reorder_level.php'))
                    ->permission(Permission::INVENTORY_REORDER_REPORT)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(70);
            })->sort(30);

            $inventory->section(Section::INVENTORY_PRICING, $this->label('Pricing and Costs'), function (SectionBuilder $section) {
                $section->page('sale-price.manage', $this->label('Sales &Pricing'))
                    ->target($this->script('inventory/prices.php'))
                    ->permission(Permission::MANAGE_SALE_PRICE)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('purchase-price.manage', $this->label('Purchasing &Pricing'))
                    ->target($this->script('inventory/purchasing_data.php'))
                    ->permission(Permission::MANAGE_PURCHASE_PRICE)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('standard-cost.manage', $this->label('Standard &Costs'))
                    ->target($this->script('inventory/cost_update.php'))
                    ->permission(Permission::MANAGE_STANDARD_COST)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(30);
            })->sort(40);

            $this->unlisted($inventory);
        });
    }

    /**
     * Places a menu never offers. Both are reached by transaction type from wherever a reference is
     * printed, so they hang off the area rather than off any one entry above them.
     */
    private function unlisted(AreaBuilder $inventory): void
    {
        $inventory->hiddenPage('adjustment.view', $this->label('View Inventory Adjustment'))
            ->target($this->script('inventory/view/view_adjustment.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_INVENTORY_TRANSACTION);

        $inventory->hiddenPage('transfer.view', $this->label('View Inventory Transfer'))
            ->target($this->script('inventory/view/view_transfer.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_INVENTORY_TRANSACTION);
    }
}
