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

use App\Foundation\Auth\Constant\Permission;

$GLOBALS['page_security'] = Permission::TAX_TRANSACTION_REPORT;
require_once __DIR__ . "/../../includes/session.inc";


require_once __DIR__ . "/../../includes/date_functions.inc";
require_once __DIR__ . "/../../includes/ui.inc";
require_once __DIR__ . "/../../includes/data_checks.inc";

require_once __DIR__ . "/../../gl/includes/gl_db.inc";

$js = '';
set_focus('account');
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(__($GLOBALS['help_context'] = "Tax Inquiry"), false, false, '', $js);

//----------------------------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('Show')) 
{
	$Ajax->activate('trans_tbl');
}

if (get_post('TransFromDate') == "" && get_post('TransToDate') == "")
{
	$date = Today();
	$row = get_company_prefs();
	$edate = add_months($date, -$row['tax_last']);
	$edate = end_month($edate);
	$bdate = begin_month($edate);
	$bdate = add_months($bdate, -$row['tax_prd'] + 1);
	$_POST["TransFromDate"] = $bdate;
	$_POST["TransToDate"] = $edate;
}	

//----------------------------------------------------------------------------------------------------

function tax_inquiry_controls()
{
    start_form();

    start_table(TABLESTYLE_NOBORDER);
	start_row();

	date_cells(__("from:"), 'TransFromDate', '', null, -user_transaction_days());
	date_cells(__("to:"), 'TransToDate');
	submit_cells('Show',__("Show"),'','', 'default');

    end_row();

	end_table();

    end_form();
}

//----------------------------------------------------------------------------------------------------

function show_results()
{
    /*Now get the transactions  */
	div_start('trans_tbl');
	start_table(TABLESTYLE);

	$th = array(__("Type"), __("Description"), __("Amount"), __("Outputs")."/".__("Inputs"));
	table_header($th);
	$k = 0;
	$total = 0;

	$taxes = get_tax_summary($_POST['TransFromDate'], $_POST['TransToDate']);

	while ($tx = db_fetch($taxes))
	{

		$payable = $tx['payable'];
		$collectible = -$tx['collectible'];
		$net = $collectible + $payable;
		$total += $net;
		alt_table_row_color($k);
		label_cell($tx['name'] . " " . $tx['rate'] . "%");
		label_cell(__("Charged on sales") . " (" . __("Output Tax")."):");
		amount_cell($payable);
		amount_cell($tx['net_output']);
		end_row();
		alt_table_row_color($k);
		label_cell($tx['name'] . " " . $tx['rate'] . "%");
		label_cell(__("Paid on purchases") . " (" . __("Input Tax")."):");
		amount_cell($collectible);
		amount_cell(-$tx['net_input']);
		end_row();
		alt_table_row_color($k);
		label_cell("<b>".$tx['name'] . " " . $tx['rate'] . "%</b>");
		label_cell("<b>".__("Net payable or collectible") . ":</b>");
		amount_cell($net, true);
		label_cell("");
		end_row();
	}	
	alt_table_row_color($k);
	label_cell("");
	label_cell("<b>".__("Total payable or refund") . ":</b>");
	amount_cell($total, true);
	label_cell("");
	end_row();

	end_table(2);
	div_end();
}

//----------------------------------------------------------------------------------------------------

tax_inquiry_controls();

show_results();

//----------------------------------------------------------------------------------------------------

end_page();

