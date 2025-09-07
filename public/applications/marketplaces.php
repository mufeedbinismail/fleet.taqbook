<?php

class marketplaces_app extends application 
{
	function __construct() 
	{
		parent::__construct("mp_orders", _($this->help_context = "Marketplace &Sales"));
	
		$this->add_module(_("Transactions"));


		$this->add_module(_("Inquiries and Reports"));


		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("Add and Manage &Marketplaces"),
			"sales/manage/marketplaces.php", 'SA_MARKETPLACE', MENU_ENTRY);
	}
}