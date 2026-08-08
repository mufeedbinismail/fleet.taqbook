<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Constant\Permission;
use App\Legacy\Navigation\Enum\Query;
use App\Foundation\Navigation\Builder\AreaBuilder;
use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\Builder\SectionBuilder;
use App\Foundation\Navigation\Constant\Area;
use App\Foundation\Navigation\Constant\Section;
use App\Foundation\Navigation\Enum\Category;
use App\Foundation\Navigation\Enum\Column;

class SaleSource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::SALE, $this->label('&Sales'), function (AreaBuilder $sale) {
            $sale->icon('icon-storefront')
                ->help('Sales')
                ->sort(10)
                ->target($this->script('index.php', ['area' => Area::SALE]));

            $sale->section(Section::SALE_TRANSACTION, $this->label('Transactions'), function (SectionBuilder $section) {
                $section->page('quotation.create', $this->label('Sales &Quotation Entry'))
                    ->target($this->script('sales/sales_order_entry.php', ['NewQuotation' => 'Yes']))
                    ->permission(Permission::CREATE_QUOTATION)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('order.create', $this->label('Sales &Order Entry'))
                    ->target($this->script('sales/sales_order_entry.php', ['NewOrder' => 'Yes']))
                    ->permission(Permission::CREATE_SALE_ORDER)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('delivery.create', $this->label('Direct &Delivery'))
                    ->target($this->script('sales/sales_order_entry.php', ['NewDelivery' => '0']))
                    ->permission(Permission::CREATE_SALE_DELIVERY)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('invoice.create', $this->label('Direct &Invoice'))
                    ->target($this->script('sales/sales_order_entry.php', ['NewInvoice' => '0']))
                    ->permission(Permission::CREATE_SALE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('delivery.against-order', $this->label('&Delivery Against Sales Orders'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['OutstandingOnly' => '1']))
                    ->permission(Permission::CREATE_SALE_DELIVERY)
                    ->category(Category::Transaction)
                    ->place(Column::Left, cluster: 1)
                    ->sort(50);

                $section->page('invoice.against-delivery', $this->label('&Invoice Against Sales Delivery'))
                    ->target($this->script('sales/inquiry/sales_deliveries_view.php', ['OutstandingOnly' => '1']))
                    ->permission(Permission::CREATE_SALE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left, cluster: 1)
                    ->sort(60);

                $section->page('invoice.prepaid', $this->label('Invoice &Prepaid Orders'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['PrepaidOrders' => 'Yes']))
                    ->permission(Permission::CREATE_SALE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Left, cluster: 1)
                    ->sort(70);

                $section->page('delivery.from-template', $this->label('&Template Delivery'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['DeliveryTemplates' => 'Yes']))
                    ->permission(Permission::CREATE_SALE_DELIVERY)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(80);

                $section->page('invoice.from-template', $this->label('&Template Invoice'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['InvoiceTemplates' => 'Yes']))
                    ->permission(Permission::CREATE_SALE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(90);

                $section->page('recurrent-invoice.create', $this->label('&Create and Print Recurrent Invoices'))
                    ->target($this->script('sales/create_recurrent_invoices.php'))
                    ->permission(Permission::CREATE_SALE_INVOICE)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(100);

                $section->page('payment.create', $this->label('Customer &Payments'))
                    ->target($this->script('sales/customer_payments.php'))
                    ->permission(Permission::CREATE_SALE_PAYMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(110);

                $section->page('credit-note.create', $this->label('Customer &Credit Notes'))
                    ->target($this->script('sales/credit_note_entry.php', ['NewCredit' => 'Yes']))
                    ->permission(Permission::CREATE_SALE_FREEHAND_CREDIT)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(120);

                $section->page('allocation.create', $this->label('&Allocate Customer Payments or Credit Notes'))
                    ->target($this->script('sales/allocations/customer_allocation_main.php'))
                    ->permission(Permission::ALLOCATE_SALE_PAYMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Right, cluster: 1)
                    ->sort(130);
            })->sort(10);

            $sale->section(Section::SALE_INQUIRY, $this->label('Inquiries and Reports'), function (SectionBuilder $section) {
                $section->page('quotation.inquire', $this->label('Sales Quotation I&nquiry'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['type' => '32']))
                    ->permission(Permission::VIEW_SALE_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('order.inquire', $this->label('Sales Order &Inquiry'))
                    ->target($this->script('sales/inquiry/sales_orders_view.php', ['type' => '30']))
                    ->permission(Permission::VIEW_SALE_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('transaction.inquire', $this->label('Customer Transaction &Inquiry'))
                    ->target($this->script('sales/inquiry/customer_inquiry.php'))
                    ->permission(Permission::VIEW_SALE_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('allocation.inquire', $this->label('Customer Allocation &Inquiry'))
                    ->target($this->script('sales/inquiry/customer_allocation_inquiry.php'))
                    ->permission(Permission::ALLOCATE_SALE_PAYMENT)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('transaction.report', $this->label('Customer and Sales &Reports'))
                    ->target($this->reports(0))
                    ->permission(Permission::VIEW_SALE_TRANSACTION)
                    ->category(Category::Report)
                    ->place(Column::Right)
                    ->sort(50);
            })->sort(20);

            $sale->section(Section::SALE_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('customer.manage', $this->label('Add and Manage &Customers'))
                    ->target($this->script('sales/manage/customers.php'))
                    ->permission(Permission::MANAGE_CUSTOMER)
                    ->category(Category::Entry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('branch.manage', $this->label('Customer &Branches'))
                    ->target($this->script('sales/manage/customer_branches.php'))
                    ->permission(Permission::MANAGE_CUSTOMER)
                    ->category(Category::Entry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('group.manage', $this->label('Sales &Groups'))
                    ->target($this->script('sales/manage/sales_groups.php'))
                    ->permission(Permission::MANAGE_SALE_GROUP)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('recurrent-invoice.manage', $this->label('Recurrent &Invoices'))
                    ->target($this->script('sales/manage/recurrent_invoices.php'))
                    ->permission(Permission::MANAGE_RECURRENT_INVOICE)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('type.manage', $this->label('Sales T&ypes'))
                    ->target($this->script('sales/manage/sales_types.php'))
                    ->permission(Permission::MANAGE_SALE_TYPE)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(50);

                $section->page('salesman.manage', $this->label('Sales &Persons'))
                    ->target($this->script('sales/manage/sales_people.php'))
                    ->permission(Permission::MANAGE_SALESMAN)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(60);

                $section->page('area.manage', $this->label('Sales &Areas'))
                    ->target($this->script('sales/manage/sales_areas.php'))
                    ->permission(Permission::MANAGE_SALE_AREA)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(70);

                $section->page('credit-status.manage', $this->label('Credit &Status Setup'))
                    ->target($this->script('sales/manage/credit_status.php'))
                    ->permission(Permission::MANAGE_CREDIT_STATUS)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(80);
            })->sort(30);

            $this->unlisted($sale);
        });
    }

    /**
     * Places a menu never offers, reached by acting on a record.
     *
     * The viewers and the edit screens hang off the area rather than off an entry, because
     * FrontAccounting reaches them by transaction type from wherever a reference is printed — an
     * inquiry row, an allocation table, a ledger drill-down. There is no one entry above them that
     * a reader came through, so claiming one would put a step in the trail that never happened.
     *
     * The rest name the entry they genuinely open out of, and read as one step below it.
     */
    private function unlisted(AreaBuilder $sale): void
    {
        $sale->hiddenPage('order.view', $this->label('View Sales Order'))
            ->target($this->script('sales/view/view_sales_order.php', ['trans_no' => Query::ANY, 'trans_type' => '30']))
            ->permission(Permission::VIEW_SALE_TRANSACTION);

        $sale->hiddenPage('quotation.view', $this->label('View Sales Quotation'))
            ->target($this->script('sales/view/view_sales_order.php', ['trans_no' => Query::ANY, 'trans_type' => '32']))
            ->permission(Permission::VIEW_SALE_TRANSACTION);

        $sale->hiddenPage('invoice.view', $this->label('View Sales Invoice'))
            ->target($this->script('sales/view/view_invoice.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_SALE_TRANSACTION);

        $sale->hiddenPage('credit-note.view', $this->label('View Credit Note'))
            ->target($this->script('sales/view/view_credit.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_SALE_TRANSACTION);

        $sale->hiddenPage('delivery.view', $this->label('View Sales Dispatch'))
            ->target($this->script('sales/view/view_dispatch.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_SALE_TRANSACTION);

        $sale->hiddenPage('payment.view', $this->label('View Customer Payment'))
            ->target($this->script('sales/view/view_receipt.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_SALE_TRANSACTION);

        $sale->hiddenPage('order.modify', $this->label('Modifying Sales Order'))
            ->target($this->script('sales/sales_order_entry.php', ['ModifyOrderNumber' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_ORDER);

        $sale->hiddenPage('quotation.modify', $this->label('Modifying Sales Quotation'))
            ->target($this->script('sales/sales_order_entry.php', ['ModifyQuotationNumber' => Query::ANY]))
            ->permission(Permission::CREATE_QUOTATION);

        $sale->hiddenPage('invoice.modify', $this->label('Modifying Sales Invoice'))
            ->target($this->script('sales/customer_invoice.php', ['ModifyInvoice' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_INVOICE);

        $sale->hiddenPage('credit-note.modify', $this->label('Modifying Credit Invoice'))
            ->target($this->script('sales/customer_credit_invoice.php', ['ModifyCredit' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_FREEHAND_CREDIT);

        $sale->hiddenPage('delivery.modify', $this->label('Modifying Delivery Note'))
            ->target($this->script('sales/customer_delivery.php', ['ModifyDelivery' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_DELIVERY);

        $sale->hiddenPage('payment.modify', $this->label('Modifying Customer Payment'))
            ->target($this->script('sales/customer_payments.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_PAYMENT);

        $sale->hiddenPage('delivery.deliver', $this->label('Deliver Items for a Sales Order'))
            ->under('trade.sale.delivery.against-order')
            ->target($this->script('sales/customer_delivery.php', ['OrderNumber' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_DELIVERY);

        $sale->hiddenPage('invoice.issue', $this->label('Issue an Invoice for Delivery Note'))
            ->under('trade.sale.invoice.against-delivery')
            ->target($this->script('sales/customer_invoice.php', ['DeliveryNumber' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_INVOICE);

        $sale->hiddenPage('invoice.batch', $this->label('Issue Batch Invoice for Delivery Notes'))
            ->under('trade.sale.invoice.against-delivery')
            ->target($this->script('sales/customer_invoice.php', ['BatchInvoice' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_INVOICE);

        // The listed entry names the deliveries still to be invoiced; dropping the filter widens it
        // to all of them, which no entry offers.
        $sale->hiddenPage('delivery.inquire-all', $this->label('Search All Deliveries'))
            ->under('trade.sale.invoice.against-delivery')
            ->target($this->script('sales/inquiry/sales_deliveries_view.php'))
            ->permission(Permission::VIEW_SALE_TRANSACTION);

        $sale->hiddenPage('invoice.prepayment', $this->label('Prepayment or Final Invoice Entry'))
            ->under('trade.sale.invoice.prepaid')
            ->target($this->script('sales/customer_invoice.php', ['AllocationNumber' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_INVOICE);

        $sale->hiddenPage('credit-note.from-invoice', $this->label('Credit all or part of an Invoice'))
            ->under('trade.sale.transaction.inquire')
            ->target($this->script('sales/customer_credit_invoice.php', ['InvoiceNumber' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_FREEHAND_CREDIT);

        $sale->hiddenPage('allocation.allocate', $this->label('Allocate Customer Payment or Credit Note'))
            ->under('trade.sale.allocation.create')
            ->target($this->script('sales/allocations/customer_allocate.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::ALLOCATE_SALE_PAYMENT);

        $sale->hiddenPage('order.from-quotation', $this->label('Sales Order Entry'))
            ->under('trade.sale.quotation.create')
            ->target($this->script('sales/sales_order_entry.php', ['NewQuoteToSalesOrder' => Query::ANY]))
            ->permission(Permission::CREATE_SALE_ORDER);
    }
}
