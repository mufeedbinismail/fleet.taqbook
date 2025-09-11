<?php

class marketplaces_app extends application 
{
	function __construct() 
	{
		parent::__construct("mp_orders", _($this->help_context = "Marketplace &Sales"));
	
		$this->add_module(_("Transactions"));
		$this->add_lapp_function(0, _("Sales &Order Entry"),
			"sales/sales_order_entry.php?Marketplace=Yes&NewOrder=Yes", 'SA_MP_SALESORDER', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Direct &Delivery"),
			"sales/sales_order_entry.php?Marketplace=Yes&NewDelivery=0", 'SA_MP_SALESDELIVERY', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Direct &Invoice"),
			"sales/sales_order_entry.php?Marketplace=Yes&NewInvoice=0", 'SA_MP_SALESINVOICE', MENU_TRANSACTION);
		$this->add_lapp_function(0, "","");
		$this->add_lapp_function(0, _("&Delivery Against Sales Orders"),
			"sales/inquiry/sales_orders_view.php?Marketplace=Yes&OutstandingOnly=1", 'SA_MP_SALESDELIVERY', MENU_TRANSACTION);

		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("Sales Order &Inquiry"),
			"sales/inquiry/sales_orders_view.php?type=30&Marketplace=Yes", 'SA_MP_SALESTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Customer Transaction &Inquiry"),
			"sales/inquiry/customer_inquiry.php?Marketplace=Yes", 'SA_MP_SALESTRANSVIEW', MENU_INQUIRY);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("Add and Manage &Marketplaces"),
			"sales/manage/marketplaces.php", 'SA_MARKETPLACE', MENU_ENTRY);
	}
}