<?php

class marketplaces_app extends application 
{
	function __construct() 
	{
		parent::__construct("mp_orders", _($this->help_context = "Marketplace &Sales"));
	
		$this->add_module(_("Transactions"));


		$this->add_module(_("Inquiries and Reports"));


		$this->add_module(_("Maintenance"));


	}
}