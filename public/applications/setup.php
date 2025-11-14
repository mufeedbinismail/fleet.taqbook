<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
class setup_app extends application
{
	function __construct()
	{
		parent::__construct("system", __($this->help_context = "S&etup"));

		$this->add_module(__("Company Setup"));
		$this->add_lapp_function(0, __("&Company Setup"),
			"admin/company_preferences.php?", 'SA_SETUPCOMPANY', MENU_SETTINGS);
		$this->add_lapp_function(0, __("&User Accounts Setup"),
			"admin/users.php?", 'SA_USERS', MENU_SETTINGS);
		$this->add_lapp_function(0, __("&Access Setup"),
			"admin/security_roles.php?", 'SA_SECROLES', MENU_SETTINGS);
		$this->add_lapp_function(0, __("&Display Setup"),
			"admin/display_prefs.php?", 'SA_SETUPDISPLAY', MENU_SETTINGS);
		$this->add_lapp_function(0, __("Transaction &References"),
			"admin/forms_setup.php?", 'SA_FORMSETUP', MENU_SETTINGS);
		$this->add_rapp_function(0, __("&Taxes"),
			"taxes/tax_types.php?", 'SA_TAXRATES', MENU_MAINTENANCE);
		$this->add_rapp_function(0, __("Tax &Groups"),
			"taxes/tax_groups.php?", 'SA_TAXGROUPS', MENU_MAINTENANCE);
		$this->add_rapp_function(0, __("Item Ta&x Types"),
			"taxes/item_tax_types.php?", 'SA_ITEMTAXTYPE', MENU_MAINTENANCE);
		$this->add_rapp_function(0, __("System and &General GL Setup"),
			"admin/gl_setup.php?", 'SA_GLSETUP', MENU_SETTINGS);
		$this->add_rapp_function(0, __("&Fiscal Years"),
			"admin/fiscalyears.php?", 'SA_FISCALYEARS', MENU_MAINTENANCE);
		$this->add_rapp_function(0, __("&Print Profiles"),
			"admin/print_profiles.php?", 'SA_PRINTPROFILE', MENU_MAINTENANCE);

		$this->add_module(__("Miscellaneous"));
		$this->add_lapp_function(1, __("Pa&yment Terms"),
			"admin/payment_terms.php?", 'SA_PAYTERMS', MENU_MAINTENANCE);
		$this->add_lapp_function(1, __("Shi&pping Company"),
			"admin/shipping_companies.php?", 'SA_SHIPPING', MENU_MAINTENANCE);
		$this->add_rapp_function(1, __("&Points of Sale"),
			"sales/manage/sales_points.php?", 'SA_POSSETUP', MENU_MAINTENANCE);
		$this->add_rapp_function(1, __("&Printers"),
			"admin/printers.php?", 'SA_PRINTERS', MENU_MAINTENANCE);
		$this->add_rapp_function(1, __("Contact &Categories"),
			"admin/crm_categories.php?", 'SA_CRMCATEGORY', MENU_MAINTENANCE);

		$this->add_module(__("Maintenance"));
		$this->add_lapp_function(2, __("&Void a Transaction"),
			"admin/void_transaction.php?", 'SA_VOIDTRANSACTION', MENU_MAINTENANCE);
		$this->add_lapp_function(2, __("View or &Print Transactions"),
			"admin/view_print_transaction.php?", 'SA_VIEWPRINTTRANSACTION', MENU_MAINTENANCE);
		$this->add_lapp_function(2, __("&Attach Documents"),
			"admin/attachments.php?filterType=20", 'SA_ATTACHDOCUMENT', MENU_MAINTENANCE);
		$this->add_rapp_function(2, __("&Backup"),
			"admin/backups.php?", 'SA_BACKUP', MENU_SYSTEM);

	}
}


