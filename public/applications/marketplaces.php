<?php

class marketplaces_app extends application 
{
	function __construct() 
	{
		parent::__construct("mp_orders", __($this->help_context = "Marketplace &Sales"));
	
		$this->add_module(__("Transactions"));
		$this->add_lapp_function(0, __("Sales &Order Entry"),
			"sales/sales_order_entry.php?Marketplace=Yes&NewOrder=Yes", 'SA_MP_SALESORDER', MENU_TRANSACTION);
		$this->add_lapp_function(0, __("Direct &Delivery"),
			"sales/sales_order_entry.php?Marketplace=Yes&NewDelivery=0", 'SA_MP_SALESDELIVERY', MENU_TRANSACTION);
		$this->add_lapp_function(0, __("Direct &Invoice"),
			"sales/sales_order_entry.php?Marketplace=Yes&NewInvoice=0", 'SA_MP_SALESINVOICE', MENU_TRANSACTION);
		$this->add_lapp_function(0, "","");
		$this->add_lapp_function(0, __("&Delivery Against Sales Orders"),
			"sales/inquiry/sales_orders_view.php?Marketplace=Yes&OutstandingOnly=1", 'SA_MP_SALESDELIVERY', MENU_TRANSACTION);
		$this->add_lapp_function(0, __("&Invoice Against Sales Delivery"),
			"sales/inquiry/sales_deliveries_view.php?Marketplace=Yes&OutstandingOnly=1", 'SA_MP_SALESINVOICE', MENU_TRANSACTION);

		$this->add_rapp_function(0, __("Customer &Payments"),
			"sales/customer_payments.php?Marketplace=Yes", 'SA_MP_SALESPAYMNT', MENU_TRANSACTION);
		$this->add_rapp_function(0, __("Customer &Credit Notes"),
			"sales/credit_note_entry.php?NewCredit=Yes&Marketplace=Yes", 'SA_MP_SALESCREDIT', MENU_TRANSACTION);
		$this->add_rapp_function(0, __("&Allocate Customer Payments or Credit Notes"),
			"sales/allocations/customer_allocation_main.php?Marketplace=Yes", 'SA_MP_SALESALLOC', MENU_TRANSACTION);
        $this->add_rapp_function(0, "","");
		$this->add_rapp_function(0, __("Supplier &Fee Invoice"),
			"marketplace/marketplace_supplier_invoice.php?New=1", 'SA_MP_SUPPINVOICE', MENU_TRANSACTION);

		$this->add_module(__("Inquiries and Reports"));
		$this->add_lapp_function(1, __("Sales Order &Inquiry"),
			"sales/inquiry/sales_orders_view.php?type=30&Marketplace=Yes", 'SA_MP_SALESTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, __("Customer Transaction &Inquiry"),
			"sales/inquiry/customer_inquiry.php?Marketplace=Yes", 'SA_MP_SALESTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, __("Customer Allocation &Inquiry"),
			"sales/inquiry/customer_allocation_inquiry.php?Marketplace=Yes", 'SA_MP_SALESALLOC', MENU_INQUIRY);
    
		$this->add_rapp_function(1, __("Supplier &Fee Invoice Inquiry"),
			"marketplace/marketplace_supplier_invoice_list.php", 'SA_MP_SUPPTRANSVIEW', MENU_INQUIRY);

		$this->add_module(__("Maintenance"));
		$this->add_lapp_function(2, __("Add and Manage &Marketplaces"),
			"sales/manage/marketplaces.php", 'SA_MARKETPLACE', MENU_ENTRY);
	}
}