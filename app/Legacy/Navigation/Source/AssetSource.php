<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Constant\Permission;
use App\Legacy\Navigation\Condition\FixedAssetsEnabled;
use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Builder\SectionBuilder;
use App\Navigation\Constant\Area;
use App\Navigation\Constant\Section;
use App\Navigation\Enum\Category;
use App\Navigation\Enum\Column;

class AssetSource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::ASSET, $this->label('&Fixed Assets'), function (AreaBuilder $asset) {
            $asset->icon('icon-fixed-assets')
                ->help('Fixed Assets')
                ->sort(60)
                ->when(FixedAssetsEnabled::class)
                ->target($this->script('admin/dashboard.php', ['sel_app' => 'assets']));

            $asset->section(Section::ASSET_TRANSACTION, $this->label('Transactions'), function (SectionBuilder $section) {
                $section->page('purchase.create', $this->label('Fixed Assets &Purchase'))
                    ->target($this->script('purchasing/po_entry_items.php', ['NewInvoice' => 'Yes', 'FixedAsset' => '1']))
                    ->permission(Permission::CREATE_PURCHASE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('transfer.create', $this->label('Fixed Assets Location &Transfers'))
                    ->target($this->script('inventory/transfers.php', ['NewTransfer' => '1', 'FixedAsset' => '1']))
                    ->permission(Permission::CREATE_ASSET_TRANSFER)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('disposal.create', $this->label('Fixed Assets &Disposal'))
                    ->target($this->script('inventory/adjustments.php', ['NewAdjustment' => '1', 'FixedAsset' => '1']))
                    ->permission(Permission::CREATE_DISPOSAL)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('sale.create', $this->label('Fixed Assets &Sale'))
                    ->target($this->script('sales/sales_order_entry.php', ['NewInvoice' => '0', 'FixedAsset' => '1']))
                    ->permission(Permission::CREATE_SALE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('depreciation.process', $this->label('Process &Depreciation'))
                    ->target($this->script('fixed_assets/process_depreciation.php'))
                    ->permission(Permission::CREATE_DEPRECIATION)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(50);
            })->sort(10);

            $asset->section(Section::ASSET_INQUIRY, $this->label('Inquiries and Reports'), function (SectionBuilder $section) {
                $section->page('movement.inquire', $this->label('Fixed Assets &Movements'))
                    ->target($this->script('inventory/inquiry/stock_movements.php', ['FixedAsset' => '1']))
                    ->permission(Permission::VIEW_ASSET_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('item.inquire', $this->label('Fixed Assets In&quiry'))
                    ->target($this->script('fixed_assets/inquiry/stock_inquiry.php'))
                    ->permission(Permission::ASSET_TRANSACTION_ANALYTICS)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('transaction.report', $this->label('Fixed Assets &Reports'))
                    ->target($this->reports(7))
                    ->permission(Permission::ASSET_TRANSACTION_ANALYTICS)
                    ->category(Category::Report)
                    ->place(Column::Right)
                    ->sort(30);
            })->sort(20);

            $asset->section(Section::ASSET_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('item.manage', $this->label('Fixed &Assets'))
                    ->target($this->script('inventory/manage/items.php', ['FixedAsset' => '1']))
                    ->permission(Permission::MANAGE_ASSET_ITEM)
                    ->category(Category::Entry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('location.manage', $this->label('Fixed Assets &Locations'))
                    ->target($this->script('inventory/manage/locations.php', ['FixedAsset' => '1']))
                    ->permission(Permission::MANAGE_INVENTORY_LOCATION)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(20);

                $section->page('category.manage', $this->label('Fixed Assets &Categories'))
                    ->target($this->script('inventory/manage/item_categories.php', ['FixedAsset' => '1']))
                    ->permission(Permission::MANAGE_ASSET_CATEGORY)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(30);

                $section->page('class.manage', $this->label('Fixed Assets Cl&asses'))
                    ->target($this->script('fixed_assets/fixed_asset_classes.php'))
                    ->permission(Permission::MANAGE_ASSET_CLASS)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(40);
            })->sort(30);
        });
    }
}
