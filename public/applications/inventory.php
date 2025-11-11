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
class inventory_app extends application
{
	function __construct()
	{
		parent::__construct("stock", __($this->help_context = "&Items and Inventory"));

		$this->add_module(__("Transactions"));
		$this->add_lapp_function(0, __("Inventory Location &Transfers"),
			"inventory/transfers.php?NewTransfer=1", 'SA_LOCATIONTRANSFER', MENU_TRANSACTION);
		$this->add_lapp_function(0, __("Inventory &Adjustments"),
			"inventory/adjustments.php?NewAdjustment=1", 'SA_INVENTORYADJUSTMENT', MENU_TRANSACTION);

		$this->add_module(__("Inquiries and Reports"));
		$this->add_lapp_function(1, __("Inventory Item &Movements"),
			"inventory/inquiry/stock_movements.php?", 'SA_ITEMSTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, __("Inventory Item &Status"),
			"inventory/inquiry/stock_status.php?", 'SA_ITEMSSTATVIEW', MENU_INQUIRY);
		$this->add_rapp_function(1, __("Inventory &Reports"),
			"reporting/reports_main.php?Class=2", 'SA_ITEMSTRANSVIEW', MENU_REPORT);

		$this->add_module(__("Maintenance"));
		$this->add_lapp_function(2, __("&Items"),
			"inventory/manage/items.php?", 'SA_ITEM', MENU_ENTRY);
		$this->add_lapp_function(2, __("&Foreign Item Codes"),
			"inventory/manage/item_codes.php?", 'SA_FORITEMCODE', MENU_MAINTENANCE);
		$this->add_lapp_function(2, __("Sales &Kits"),
			"inventory/manage/sales_kits.php?", 'SA_SALESKIT', MENU_MAINTENANCE);
		$this->add_lapp_function(2, __("Item &Categories"),
			"inventory/manage/item_categories.php?", 'SA_ITEMCATEGORY', MENU_MAINTENANCE);
		$this->add_rapp_function(2, __("Inventory &Locations"),
			"inventory/manage/locations.php?", 'SA_INVENTORYLOCATION', MENU_MAINTENANCE);
		$this->add_rapp_function(2, __("&Units of Measure"),
			"inventory/manage/item_units.php?", 'SA_UOM', MENU_MAINTENANCE);
		$this->add_rapp_function(2, __("&Reorder Levels"),
			"inventory/reorder_level.php?", 'SA_REORDER', MENU_MAINTENANCE);

		$this->add_module(__("Pricing and Costs"));
		$this->add_lapp_function(3, __("Sales &Pricing"),
			"inventory/prices.php?", 'SA_SALESPRICE', MENU_MAINTENANCE);
		$this->add_lapp_function(3, __("Purchasing &Pricing"),
			"inventory/purchasing_data.php?", 'SA_PURCHASEPRICING', MENU_MAINTENANCE);
		$this->add_rapp_function(3, __("Standard &Costs"),
			"inventory/cost_update.php?", 'SA_STANDARDCOST', MENU_MAINTENANCE);

	}
}


