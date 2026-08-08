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

$GLOBALS['page_security'] = Permission::VIEW_BANKING_TRANSACTION;

require __DIR__ . "/../../includes/session.inc";

page(__($GLOBALS['help_context'] = "View Bank Deposit"), true);

require_once __DIR__ . "/../../includes/date_functions.inc";
require_once __DIR__ . "/../../includes/ui.inc";

require_once __DIR__ . "/../../gl/includes/gl_db.inc";

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

// get the pay-to bank payment info
$result = get_bank_trans(ST_BANKDEPOSIT, $trans_no);

if (db_num_rows($result) != 1)
	display_db_error("duplicate payment bank transaction found", "");

$to_trans = db_fetch($result);

$company_currency = get_company_currency();

$show_currencies = false;

if ($to_trans['bank_curr_code'] != $to_trans['settle_curr'])
{
	$show_currencies = true;
}

if (!empty($SysPrefs->prefs['company_logo_on_views']))
	company_logo_on_view();

echo "<center>";

display_heading(__("GL Deposit") . " #$trans_no");

echo "<br>";
start_table(TABLESTYLE, "width='80%'");

if ($show_currencies)
{
	$colspan1 = 1;
	$colspan2 = 7;
}
else
{
	$colspan1 = 3;
	$colspan2 = 5;
}
start_row();
label_cells(__("To Bank Account"), $to_trans['bank_account_name'], "class='tableheader2'");
if ($show_currencies)
	label_cells(__("Currency"), $to_trans['bank_curr_code'], "class='tableheader2'");
label_cells(__("Amount"), number_format2($to_trans['amount'], user_price_dec()), "class='tableheader2'", "align=right");
label_cells(__("Date"), sql2date($to_trans['trans_date']), "class='tableheader2'");
end_row();
start_row();
label_cells(__("From"), get_counterparty_name(ST_BANKDEPOSIT, $to_trans['trans_no']), "class='tableheader2'", "colspan=$colspan1");
if ($show_currencies)
{
	label_cells(__("Settle currency"), $to_trans['settle_curr'], "class='tableheader2'");
	label_cells(__("Settled amount"),  number_format2($to_trans['settled_amount'], user_price_dec()), "class='tableheader2'");
}
label_cells(__("Deposit Type"), $bank_transfer_types[$to_trans['account_type']], "class='tableheader2'");
end_row();
start_row();
label_cells(__("Reference"), $to_trans['ref'], "class='tableheader2'", "colspan=$colspan2");
end_row();
comments_display_row(ST_BANKDEPOSIT, $trans_no);

end_table(1);

is_voided_display(ST_BANKDEPOSIT, $trans_no, __("This deposit has been voided."));

$items = get_gl_trans(ST_BANKDEPOSIT, $trans_no);

if (db_num_rows($items) == 0)
{
	display_note(__("There are no items for this deposit."));
}
else
{

	display_heading2(__("Items for this Deposit"));
	if ($show_currencies)
		display_heading2(__("Item Amounts are Shown in:") . " " . $company_currency);

    start_table(TABLESTYLE, "width='80%'");
    $dim = get_company_pref('use_dimension');
    if ($dim == 2)
        $th = array(__("Account Code"), __("Account Description"), __("Dimension")." 1", __("Dimension")." 2",
            __("Amount"), __("Memo"));
    elseif ($dim == 1)
        $th = array(__("Account Code"), __("Account Description"), __("Dimension"),
            __("Amount"), __("Memo"));
    else
        $th = array(__("Account Code"), __("Account Description"),
            __("Amount"), __("Memo"));
    table_header($th);

    $k = 0; //row colour counter
	$total_amount = 0;

    while ($item = db_fetch($items))
    {

		if ($item["account"] != $to_trans["account_code"])
		{
    		alt_table_row_color($k);

        	label_cell($item["account"]);
    		label_cell($item["account_name"]);
            if ($dim >= 1)
                label_cell(get_dimension_string($item['dimension_id'], true));
            if ($dim > 1)
                label_cell(get_dimension_string($item['dimension2_id'], true));
            amount_cell(-$item["amount"]);
    		label_cell($item["memo_"]);
    		end_row();
    		$total_amount += $item["amount"];
		}
	}

	label_row(__("Total"), number_format2(-$total_amount, user_price_dec()),"colspan=".(2+$dim)." align=right", "align=right");

	end_table(1);

	display_allocations_from($to_trans['person_type_id'], $to_trans['person_id'], 2, $trans_no, $to_trans['settled_amount']);
}

end_page(true, false, false, ST_BANKDEPOSIT, $trans_no);
