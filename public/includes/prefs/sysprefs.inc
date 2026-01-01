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

use App\Foundation\Setting\SettingRepository;

require_once __DIR__ . "/../../admin/db/company_db.inc";

/*
	System and per company prefferences.
	Object is created only with site wide preffernces.
	After user login refresh method have to be called to re-initialize company specific settings.
*/
class sys_prefs extends stdClass
{
	public SettingRepository $prefs;

	function __construct()
	{

		// retrieve set system wide preferences
		require __DIR__ . '/../../config.default.php';
		if (file_exists(__DIR__ . '/../../config.php'))
			require __DIR__ . '/../../config.php';

		foreach(get_defined_vars() as $name => $value)
			$this->$name = $value;

        $this->app_title = config('app.name');
        $this->power_by = config('app.name');
        $this->power_url = '#';
        $this->help_base_url = '#?n=Help';

		if (!property_exists($this, 'time_zone') or !$this->time_zone)
			$this->time_zone = ini_get('date.timezone');

		if (!$this->time_zone)
			$this->time_zone = 'Europe/Berlin';
			
		if (!isset($this->use_popup_search))
			$this->use_popup_search = false;

   	    ini_set('date.timezone', $this->time_zone);

        $this->prefs = settings();
	}

	/*
		Re-initialize company preferences.
	*/
	function refresh()
	{
		settings()->refresh();
	}

	function allow_negative_stock() 
	{
		return $this->prefs['allow_negative_stock'];
	}

    /* Sherifoz 26.06.03 Proportion by which items can be received over the quantity that is specified in a purchase
    invoice
    The figure entered is interpreted as a percentage ie 10 means 0.1 or 10% not 10 times
    */
	function over_receive_allowance() 
	{
		return $this->prefs['po_over_receive'];
	}
	
    /* Proportion by which a purchase invoice line is an overcharge for a purchase order item received
    is an overcharge. If the overcharge is more than this percentage then an error is reported and
    purchase invoice line cannot be entered
    The figure entered is interpreted as a percentage ie 20 means 0.2 or 20% not 20 times
    */	
	function over_charge_allowance() 
	{
		return $this->prefs['po_over_charge'];
	}
	
	function default_credit_limit() 
	{
		return $this->prefs['default_credit_limit'];
	}				
	
	function default_wo_required_by() 
	{
		return $this->prefs['default_workorder_required'];
	}

	function default_quote_valid_days() 
	{
		return $this->prefs['default_quote_valid_days'];
	}

	function default_delivery_required_by() 
	{
		return $this->prefs['default_delivery_required'];
	}

	function default_receival_required_by() 
	{
		return $this->prefs['default_receival_required'];
	}

	function default_dimension_required_by() 
	{
		return $this->prefs['default_dim_required'];
	}	
	
	function auto_currency_revaluation() 
	{
		return $this->prefs['auto_curr_reval'];
	}	
	
	function allocation_settled_allowance()
	{
		return $this->config_allocation_settled_allowance;
	}

	function no_zero_lines_amount() 
	{
		return $this->prefs['no_zero_lines_amount'];
	}

	function show_po_item_codes() 
	{
		return $this->prefs['show_po_item_codes'];
	}

	function accounts_alpha() 
	{
		return $this->prefs['accounts_alpha'];
	}

	function loc_notification() 
	{
		return $this->prefs['loc_notification'];
	}

	function print_invoice_no() 
	{
		return $this->prefs['print_invoice_no'];
	}

	function allow_negative_prices() 
	{
		return $this->prefs['allow_negative_prices'];
	}

	function print_item_images_on_quote() 
	{
		return $this->prefs['print_item_images_on_quote'];
	}

	function alternative_tax_include_on_docs() 
	{
		return $this->prefs['alternative_tax_include_on_docs'];
	}

	function suppress_tax_rates() 
	{
		return $this->prefs['suppress_tax_rates'];
	}

	function backup_dir($comp=null)
	{
		if (!isset($comp))
			$comp = user_company();

		if (isset($this->backup_path))
			return sprintf($this->backup_path, $comp);
		else
			return $this->comp_path.'/'.$comp."/backup/";
	}
}

