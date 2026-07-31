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
use App\Shared\Enum\SystemType;

class MarketplaceSource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::MARKETPLACE, $this->label('Marketplace &Sales'), function (AreaBuilder $marketplace) {
            $marketplace->icon('icon-storefront')
                ->help('Marketplace Sales')
                ->sort(20)
                ->target($this->script('admin/dashboard.php', ['sel_app' => 'mp_orders']));

            $marketplace->section(Section::MARKETPLACE_TRANSACTION, $this->label('Transactions'), function (SectionBuilder $section) {
                $section->page('order.create', $this->label('Sales &Order Entry'))
                    ->target($this->script('sales/sales_order_entry.php', ['Marketplace' => 'Yes', 'NewOrder' => 'Yes']))
                    ->permission(Permission::CREATE_MARKETPLACE_ORDER)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('delivery.create', $this->label('Direct &Delivery'))
                    ->target($this->script('sales/sales_order_entry.php', ['Marketplace' => 'Yes', 'NewDelivery' => '0']))
                    ->permission(Permission::CREATE_MARKETPLACE_DELIVERY)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('invoice.create', $this->label('Direct &Invoice'))
                    ->target($this->script('sales/sales_order_entry.php', ['Marketplace' => 'Yes', 'NewInvoice' => '0']))
                    ->permission(Permission::CREATE_MARKETPLACE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('delivery.against-order', $this->label('&Delivery Against Sales Orders'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['Marketplace' => 'Yes', 'OutstandingOnly' => '1']))
                    ->permission(Permission::CREATE_MARKETPLACE_DELIVERY)
                    ->category(Category::Transaction)
                    ->place(Column::Left, cluster: 1)
                    ->sort(40);

                $section->page('invoice.against-delivery', $this->label('&Invoice Against Sales Delivery'))
                    ->target($this->script('sales/inquiry/sales_deliveries_view.php', ['Marketplace' => 'Yes', 'OutstandingOnly' => '1']))
                    ->permission(Permission::CREATE_MARKETPLACE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left, cluster: 1)
                    ->sort(50);

                $section->page('payment.create', $this->label('Customer &Payments'))
                    ->target($this->script('marketplace/marketplace_customer_settlement.php', ['New' => '1']))
                    ->permission(Permission::CREATE_MARKETPLACE_PAYMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(60);

                $section->page('credit-note.create', $this->label('Customer &Credit Notes'))
                    ->target($this->script('sales/credit_note_entry.php', ['NewCredit' => 'Yes', 'Marketplace' => 'Yes']))
                    ->permission(Permission::CREATE_MARKETPLACE_FREEHAND_CREDIT)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(70);

                $section->page('refund.create', $this->label('Customer Re&funds'))
                    ->target($this->script('marketplace/marketplace_customer_settlement.php', ['New' => '1', 'Refund' => '1']))
                    ->permission(Permission::CREATE_MARKETPLACE_PAYMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(80);

                $section->page('supplier-invoice.create', $this->label('Supplier &Fee Invoice'))
                    ->target($this->script('marketplace/marketplace_supplier_trans.php', [
                        'type' => SystemType::SupplierInvoice->value,
                        'New' => '1',
                    ]))
                    ->permission(Permission::CREATE_MARKETPLACE_SUPPLIER_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(90);

                $section->page('supplier-credit.create', $this->label('Supplier Fee Credit &Note'))
                    ->target($this->script('marketplace/marketplace_supplier_trans.php', [
                        'type' => SystemType::SupplierCredit->value,
                        'New' => '1',
                    ]))
                    ->permission(Permission::CREATE_MARKETPLACE_SUPPLIER_CREDIT)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(100);
            })->sort(10);

            $marketplace->section(Section::MARKETPLACE_INQUIRY, $this->label('Inquiries and Reports'), function (SectionBuilder $section) {
                $section->page('order.inquire', $this->label('Sales Order &Inquiry'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['type' => '30', 'Marketplace' => 'Yes']))
                    ->permission(Permission::VIEW_MARKETPLACE_SALE_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('transaction.inquire', $this->label('Customer Transaction &Inquiry'))
                    ->target($this->script('sales/inquiry/customer_inquiry.php', ['Marketplace' => 'Yes']))
                    ->permission(Permission::VIEW_MARKETPLACE_SALE_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('supplier-invoice.inquire', $this->label('Supplier &Fee Invoice Inquiry'))
                    ->target($this->script('marketplace/marketplace_supplier_trans_list.php'))
                    ->permission(Permission::VIEW_MARKETPLACE_SUPPLIER_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Right)
                    ->sort(30);

                $section->page('sale.report', $this->label('Customer and Sales &Reports'))
                    ->target($this->reports(0))
                    ->permission(Permission::VIEW_MARKETPLACE_SALE_TRANSACTION)
                    ->category(Category::Report)
                    ->place(Column::Right, cluster: 1)
                    ->sort(40);

                $section->page('purchase.report', $this->label('Supplier and Purchasing &Reports'))
                    ->target($this->reports(1))
                    ->permission(Permission::VIEW_MARKETPLACE_SUPPLIER_TRANSACTION)
                    ->category(Category::Report)
                    ->place(Column::Right, cluster: 1)
                    ->sort(50);
            })->sort(20);

            $marketplace->section(Section::MARKETPLACE_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('channel.manage', $this->label('Add and Manage &Marketplaces'))
                    ->target($this->script('sales/manage/marketplaces.php'))
                    ->permission(Permission::MANAGE_MARKETPLACE_CHANNEL)
                    ->category(Category::Entry)
                    ->place(Column::Left)
                    ->sort(10);
            })->sort(30);

            $this->unlisted($marketplace);
        });
    }

    /**
     * Places a menu never offers. All three are reached by transaction type from wherever a
     * reference is printed, so they hang off the area rather than off any one entry above them.
     */
    private function unlisted(AreaBuilder $marketplace): void
    {
        $marketplace->hiddenPage('payment.view', $this->label('View Marketplace Customer Payment'))
            ->target($this->script('sales/view/view_marketplace_receipt.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_MARKETPLACE_SALE_TRANSACTION);

        $marketplace->hiddenPage('refund.view', $this->label('View Customer Refund'))
            ->target($this->script('sales/view/view_refund.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_MARKETPLACE_SALE_TRANSACTION);

        $marketplace->hiddenPage('supplier-transaction.view', $this->label('View Marketplace Supplier Transaction'))
            ->target($this->script('marketplace/view_marketplace_supplier_trans.php', ['trans_id' => Query::ANY]))
            ->permission(Permission::VIEW_MARKETPLACE_SUPPLIER_TRANSACTION);
    }
}
