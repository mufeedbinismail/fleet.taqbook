<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Auth\Constant\Permission;
use App\Legacy\Navigation\Enum\Query;
use App\Foundation\Navigation\Builder\AreaBuilder;
use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\Builder\SectionBuilder;
use App\Foundation\Navigation\Constant\Area;
use App\Foundation\Navigation\Constant\Section;
use App\Foundation\Navigation\Enum\Category;
use App\Foundation\Navigation\Enum\Column;

class PurchaseSource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::PURCHASE, $this->label('&Purchases'), function (AreaBuilder $purchase) {
            $purchase->icon('icon-procurement')
                ->help('Purchases')
                ->sort(30)
                ->target($this->script('index.php', ['area' => Area::PURCHASE]));

            $purchase->section(Section::PURCHASE_TRANSACTION, $this->label('Transactions'), function (SectionBuilder $section) {
                $section->page('order.create', $this->label('Purchase &Order Entry'))
                    ->target($this->script('purchasing/po_entry_items.php', ['NewOrder' => 'Yes']))
                    ->permission(Permission::CREATE_PURCHASE_ORDER)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('order.outstanding', $this->label('&Outstanding Purchase Orders Maintenance'))
                    ->target($this->script('purchasing/inquiry/po_search.php'))
                    ->permission(Permission::CREATE_PURCHASE_RECEIVAL)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('receival.create', $this->label('Direct &GRN'))
                    ->target($this->script('purchasing/po_entry_items.php', ['NewGRN' => 'Yes']))
                    ->permission(Permission::CREATE_PURCHASE_RECEIVAL)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('invoice.direct', $this->label('Direct Supplier &Invoice'))
                    ->target($this->script('purchasing/po_entry_items.php', ['NewInvoice' => 'Yes']))
                    ->permission(Permission::CREATE_PURCHASE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('payment.create', $this->label('&Payments to Suppliers'))
                    ->target($this->script('purchasing/supplier_payment.php'))
                    ->permission(Permission::CREATE_PURCHASE_PAYMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(50);

                $section->page('invoice.create', $this->label('Supplier &Invoices'))
                    ->target($this->script('purchasing/supplier_invoice.php', ['New' => '1']))
                    ->permission(Permission::CREATE_PURCHASE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(60);

                $section->page('credit-note.create', $this->label('Supplier &Credit Notes'))
                    ->target($this->script('purchasing/supplier_credit.php', ['New' => '1']))
                    ->permission(Permission::CREATE_PURCHASE_CREDIT_NOTE)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(70);

                $section->page('allocation.create', $this->label('&Allocate Supplier Payments or Credit Notes'))
                    ->target($this->script('purchasing/allocations/supplier_allocation_main.php'))
                    ->permission(Permission::ALLOCATE_PURCHASE_PAYMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(80);
            })->sort(10);

            $purchase->section(Section::PURCHASE_INQUIRY, $this->label('Inquiries and Reports'), function (SectionBuilder $section) {
                $section->page('order.inquire', $this->label('Purchase Orders &Inquiry'))
                    ->target($this->script('purchasing/inquiry/po_search_completed.php'))
                    ->permission(Permission::VIEW_PURCHASE_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('transaction.inquire', $this->label('Supplier Transaction &Inquiry'))
                    ->target($this->script('purchasing/inquiry/supplier_inquiry.php'))
                    ->permission(Permission::VIEW_PURCHASE_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('allocation.inquire', $this->label('Supplier Allocation &Inquiry'))
                    ->target($this->script('purchasing/inquiry/supplier_allocation_inquiry.php'))
                    ->permission(Permission::ALLOCATE_PURCHASE_PAYMENT)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('transaction.report', $this->label('Supplier and Purchasing &Reports'))
                    ->target($this->reports(1))
                    ->permission(Permission::VIEW_PURCHASE_TRANSACTION)
                    ->category(Category::Report)
                    ->place(Column::Right)
                    ->sort(40);
            })->sort(20);

            $purchase->section(Section::PURCHASE_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('supplier.manage', $this->label('&Suppliers'))
                    ->target($this->script('purchasing/manage/suppliers.php'))
                    ->permission(Permission::MANAGE_SUPPLIER)
                    ->category(Category::Entry)
                    ->place(Column::Left)
                    ->sort(10);
            })->sort(30);

            $this->unlisted($purchase);
        });
    }

    /**
     * Places a menu never offers, reached by acting on a record.
     *
     * The viewers and the edit screens hang off the area rather than off an entry: they are reached
     * by transaction type from wherever a reference is printed, so no single entry above them is
     * the one a reader came through.
     */
    private function unlisted(AreaBuilder $purchase): void
    {
        $purchase->hiddenPage('order.view', $this->label('View Purchase Order'))
            ->target($this->script('purchasing/view/view_po.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_PURCHASE_TRANSACTION);

        $purchase->hiddenPage('invoice.view', $this->label('View Supplier Invoice'))
            ->target($this->script('purchasing/view/view_supp_invoice.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_PURCHASE_TRANSACTION);

        $purchase->hiddenPage('credit-note.view', $this->label('View Supplier Credit Note'))
            ->target($this->script('purchasing/view/view_supp_credit.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_PURCHASE_TRANSACTION);

        $purchase->hiddenPage('payment.view', $this->label('View Payment to Supplier'))
            ->target($this->script('purchasing/view/view_supp_payment.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_PURCHASE_TRANSACTION);

        $purchase->hiddenPage('receival.view', $this->label('View Purchase Order Delivery'))
            ->target($this->script('purchasing/view/view_grn.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_PURCHASE_TRANSACTION);

        $purchase->hiddenPage('order.modify', $this->label('Modify Purchase Order'))
            ->target($this->script('purchasing/po_entry_items.php', ['ModifyOrderNumber' => Query::ANY]))
            ->permission(Permission::CREATE_PURCHASE_ORDER);

        $purchase->hiddenPage('invoice.modify', $this->label('Modifying Purchase Invoice'))
            ->target($this->script('purchasing/supplier_invoice.php', ['ModifyInvoice' => Query::ANY]))
            ->permission(Permission::CREATE_PURCHASE_INVOICE);

        $purchase->hiddenPage('credit-note.modify', $this->label('Modifying Supplier Credit'))
            ->target($this->script('purchasing/supplier_credit.php', ['ModifyCredit' => Query::ANY]))
            ->permission(Permission::CREATE_PURCHASE_CREDIT_NOTE);

        $purchase->hiddenPage('order.receive', $this->label('Receive Purchase Order Items'))
            ->under('trade.purchase.order.create')
            ->target($this->script('purchasing/po_receive_items.php', ['PONumber' => Query::ANY]))
            ->permission(Permission::CREATE_PURCHASE_RECEIVAL);

        $purchase->hiddenPage('allocation.allocate', $this->label('Allocate Supplier Payment or Credit Note'))
            ->under('trade.purchase.allocation.create')
            ->target($this->script('purchasing/allocations/supplier_allocate.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::ALLOCATE_PURCHASE_PAYMENT);
    }
}
