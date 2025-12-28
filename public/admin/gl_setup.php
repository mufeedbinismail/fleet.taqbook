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
$GLOBALS['page_security'] = 'SA_GLSETUP';
require __DIR__ . "/../includes/session.inc";

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);

page(__($GLOBALS['help_context'] = "System and General GL Setup"), false, false, "", $js);

require_once __DIR__ . "/../includes/date_functions.inc";
require_once __DIR__ . "/../includes/ui.inc";
require_once __DIR__ . "/../includes/data_checks.inc";
require_once __DIR__ . "/../admin/db/company_db.inc";

//-------------------------------------------------------------------------------------------------

function can_process()
{
    if (!check_num('past_due_days', 0, 100))
    {
        display_error(__("The past due days interval allowance must be between 0 and 100."));
        set_focus('past_due_days');
        return false;
    }

    if (!check_num('default_quote_valid_days', 0))
    {
        display_error(__("Quote Valid Days is not valid number."));
        set_focus('default_quote_valid_days');
        return false;
    }

    if (!check_num('default_delivery_required', 0))
    {
        display_error(__("Delivery Required By is not valid number."));
        set_focus('default_delivery_required');
        return false;
    }

    if (!check_num('default_receival_required', 0))
    {
        display_error(__("Receival Required By is not valid number."));
        set_focus('default_receival_required');
        return false;
    }

    if (!check_num('default_workorder_required', 0))
    {
        display_error(__("Work Order Required By After is not valid number."));
        set_focus('default_workorder_required');
        return false;
    }	

    if (!check_num('po_over_receive', 0, 100))
	{
		display_error(__("The delivery over-receive allowance must be between 0 and 100."));
		set_focus('po_over_receive');
		return false;
	}

	if (!check_num('po_over_charge', 0, 100))
	{
		display_error(__("The invoice over-charge allowance must be between 0 and 100."));
		set_focus('po_over_charge');
		return false;
	}

	if (!check_num('past_due_days', 0, 100))
	{
		display_error(__("The past due days interval allowance must be between 0 and 100."));
		set_focus('past_due_days');
		return false;
	}

	$grn_act = get_company_pref('grn_clearing_act');
	$post_grn_act = get_post('grn_clearing_act');
	if (($post_grn_act != $grn_act) && db_num_rows(get_grn_items(0, '', true)))
	{
		display_error(__("Before GRN Clearing Account can be changed all GRNs have to be invoiced"));
		$_POST['grn_clearing_act'] = $grn_act;
		set_focus('grn_clearing_account');
		return false;
	}
	if (!is_account_balancesheet(get_post('retained_earnings_act')) || is_account_balancesheet(get_post('profit_loss_year_act')))
	{
		display_error(__("The Retained Earnings Account should be a Balance Account or the Profit and Loss Year Account should be an Expense Account (preferred the last one in the Expense Class)"));
		return false;
	}

    if (session('wa_current_user')->check_application_access(session('App')->get_application('mp_orders'))) {
        if (!get_post('marketplace_expense_items')) {
            display_error(__("Please select the marketplace expense items."));
            set_focus('marketplace_expense_items');
            return false;
        }
    }
	return true;
}

//-------------------------------------------------------------------------------------------------

if (isset($_POST['submit']) && can_process())
{
    $_POST['marketplace_expense_items'] = implode(',', $_POST['marketplace_expense_items'] ?? []);
	update_company_prefs( get_post( array(
        'retained_earnings_act',
        'profit_loss_year_act',
		'debtors_act',
        'pyt_discount_act',
        'creditors_act',
        'freight_act',
        'deferred_income_act',
		'exchange_diff_act',
        'bank_charge_act',
        'default_sales_act',
        'default_sales_discount_act',
		'default_prompt_payment_act',
        'default_inventory_act',
        'default_cogs_act',
        'depreciation_period',
		'default_loss_on_asset_disposal_act',
        'default_adj_act',
        'default_inv_sales_act',
        'default_wip_act',
        'legal_text',
		'past_due_days',
        'default_workorder_required',
        'default_dim_required',
        'default_receival_required',
		'default_delivery_required',
        'default_quote_valid_days',
        'grn_clearing_act',
        'tax_algorithm',
		'no_zero_lines_amount',
        'show_po_item_codes',
        'accounts_alpha',
        'loc_notification',
        'print_invoice_no',
		'allow_negative_prices',
        'print_item_images_on_quote',
		'allow_negative_stock'=> 0,
        'accumulate_shipping'=> 0,
		'po_over_receive' => 0.0,
        'po_over_charge' => 0.0,
        'default_credit_limit'=>0.0,
        'marketplace_commission_act',
        'marketplace_shipping_act',
        'marketplace_expense_items',
    )));

	display_notification(__("The general GL setup has been updated."));

} /* end of if submit */

//-------------------------------------------------------------------------------------------------

start_form();

start_outer_table(TABLESTYLE2);

table_section(1);

$myrow = get_company_prefs();

$_POST['retained_earnings_act']  = $myrow["retained_earnings_act"];
$_POST['profit_loss_year_act']  = $myrow["profit_loss_year_act"];
$_POST['debtors_act']  = $myrow["debtors_act"];
$_POST['creditors_act']  = $myrow["creditors_act"];
$_POST['freight_act'] = $myrow["freight_act"];
$_POST['deferred_income_act'] = $myrow["deferred_income_act"];
$_POST['pyt_discount_act']  = $myrow["pyt_discount_act"];

$_POST['exchange_diff_act'] = $myrow["exchange_diff_act"];
$_POST['bank_charge_act'] = $myrow["bank_charge_act"];
$_POST['tax_algorithm'] = $myrow["tax_algorithm"];
$_POST['default_sales_act'] = $myrow["default_sales_act"];
$_POST['default_sales_discount_act']  = $myrow["default_sales_discount_act"];
$_POST['default_prompt_payment_act']  = $myrow["default_prompt_payment_act"];

$_POST['default_inventory_act'] = $myrow["default_inventory_act"];
$_POST['default_cogs_act'] = $myrow["default_cogs_act"];
$_POST['default_adj_act'] = $myrow["default_adj_act"];
$_POST['default_inv_sales_act'] = $myrow['default_inv_sales_act'];
$_POST['default_wip_act'] = $myrow['default_wip_act'];

$_POST['allow_negative_stock'] = $myrow['allow_negative_stock'];

$_POST['po_over_receive'] = percent_format($myrow['po_over_receive']);
$_POST['po_over_charge'] = percent_format($myrow['po_over_charge']);
$_POST['past_due_days'] = $myrow['past_due_days'];

$_POST['grn_clearing_act'] = $myrow['grn_clearing_act'];

$_POST['default_credit_limit'] = price_format($myrow['default_credit_limit']);
$_POST['legal_text'] = $myrow['legal_text'];
$_POST['accumulate_shipping'] = $myrow['accumulate_shipping'];

$_POST['default_workorder_required'] = $myrow['default_workorder_required'];
$_POST['default_dim_required'] = $myrow['default_dim_required'];
$_POST['default_delivery_required'] = $myrow['default_delivery_required'];
$_POST['default_receival_required'] = $myrow['default_receival_required'];
$_POST['default_quote_valid_days'] = $myrow['default_quote_valid_days'];
$_POST['no_zero_lines_amount'] = $myrow['no_zero_lines_amount'];
$_POST['show_po_item_codes'] = $myrow['show_po_item_codes'];
$_POST['accounts_alpha'] = $myrow['accounts_alpha'];
$_POST['loc_notification'] = $myrow['loc_notification'];
$_POST['print_invoice_no'] = $myrow['print_invoice_no'];
$_POST['allow_negative_prices'] = $myrow['allow_negative_prices'];
$_POST['print_item_images_on_quote'] = $myrow['print_item_images_on_quote'];
$_POST['default_loss_on_asset_disposal_act'] = $myrow['default_loss_on_asset_disposal_act'];
$_POST['depreciation_period'] = $myrow['depreciation_period'];
$_POST['marketplace_expense_items'] = array_filter(explode(',', $myrow['marketplace_expense_items']));

//---------------


table_section_title(__("General GL"));

text_row(__("Past Due Days Interval:"), 'past_due_days', $_POST['past_due_days'], 6, 6, '', "", __("days"));

accounts_type_list_row(__("Accounts Type:"), 'accounts_alpha', $_POST['accounts_alpha']); 

gl_all_accounts_list_row(__("Retained Earnings:"), 'retained_earnings_act', $_POST['retained_earnings_act']);

gl_all_accounts_list_row(__("Profit/Loss Year:"), 'profit_loss_year_act', $_POST['profit_loss_year_act']);

gl_all_accounts_list_row(__("Exchange Variances Account:"), 'exchange_diff_act', $_POST['exchange_diff_act']);

gl_all_accounts_list_row(__("Bank Charges Account:"), 'bank_charge_act', $_POST['bank_charge_act']);

tax_algorithm_list_row(__("Tax Algorithm:"), 'tax_algorithm', $_POST['tax_algorithm']);

//---------------

table_section_title(__("Dimension Defaults"));

text_row(__("Dimension Required By After:"), 'default_dim_required', $_POST['default_dim_required'], 6, 6, '', "", __("days"));

//----------------

table_section_title(__("Customers and Sales"));

amount_row(__("Default Credit Limit:"), 'default_credit_limit', $_POST['default_credit_limit']);

yesno_list_row(__("Invoice Identification:"), 'print_invoice_no', $_POST['print_invoice_no'], $name_yes=__("Number"), $name_no=__("Reference"));

check_row(__("Accumulate batch shipping:"), 'accumulate_shipping', null);

check_row(__("Print Item Image on Quote:"), 'print_item_images_on_quote', null);

textarea_row(__("Legal Text on Invoice:"), 'legal_text', $_POST['legal_text'], 32, 4);

gl_all_accounts_list_row(__("Shipping Charged Account:"), 'freight_act', $_POST['freight_act']);

gl_all_accounts_list_row(__("Deferred Income Account:"), 'deferred_income_act', $_POST['deferred_income_act'], true, false,
	__("Not used"), false, false, false);

//---------------

table_section_title(__("Customers and Sales Defaults"));
// default for customer branch
gl_all_accounts_list_row(__("Receivable Account:"), 'debtors_act');

gl_all_accounts_list_row(__("Sales Account:"), 'default_sales_act', null,
	false, false, true);

gl_all_accounts_list_row(__("Sales Discount Account:"), 'default_sales_discount_act');

gl_all_accounts_list_row(__("Prompt Payment Discount Account:"), 'default_prompt_payment_act');

text_row(__("Quote Valid Days:"), 'default_quote_valid_days', $_POST['default_quote_valid_days'], 6, 6, '', "", __("days"));

text_row(__("Delivery Required By:"), 'default_delivery_required', $_POST['default_delivery_required'], 6, 6, '', "", __("days"));

//---------------

table_section(2);

if (session('wa_current_user')->check_application_access(session('App')->get_application('mp_orders'))) {
    table_section_title(__("Marketplace Sales Defaults"));

    start_row();
    label_cells(
        __("Expense Items"),
        stock_items_list(
            'marketplace_expense_items',
            null,
            false,
            false,
            [
                'search_box' => false,
                'where' => ["mb_flag = 'D'"],
                'multi' => true,
            ]
        ),
        "class='label'"
    );
    end_row();
}

table_section_title(__("Suppliers and Purchasing"));

percent_row(__("Delivery Over-Receive Allowance:"), 'po_over_receive');

percent_row(__("Invoice Over-Charge Allowance:"), 'po_over_charge');

table_section_title(__("Suppliers and Purchasing Defaults"));

gl_all_accounts_list_row(__("Payable Account:"), 'creditors_act', $_POST['creditors_act']);

gl_all_accounts_list_row(__("Purchase Discount Account:"), 'pyt_discount_act', $_POST['pyt_discount_act']);

gl_all_accounts_list_row(__("GRN Clearing Account:"), 'grn_clearing_act', get_post('grn_clearing_act'), true, false, __("No postings on GRN"));

text_row(__("Receival Required By:"), 'default_receival_required', $_POST['default_receival_required'], 6, 6, '', "", __("days"));

check_row(__("Show PO item codes:"), 'show_po_item_codes', null);

table_section_title(__("Inventory"));

check_row(__("Allow Negative Inventory:"), 'allow_negative_stock', null);
label_row(null, __("Warning:  This may cause a delay in GL postings"), "", "class='stockmankofg' colspan=2"); 

check_row(__("No zero-amounts (Service):"), 'no_zero_lines_amount', null);

check_row(__("Location Notifications:"), 'loc_notification', null);

check_row(__("Allow Negative Prices:"), 'allow_negative_prices', null);

table_section_title(__("Items Defaults"));
gl_all_accounts_list_row(__("Sales Account:"), 'default_inv_sales_act', $_POST['default_inv_sales_act']);

gl_all_accounts_list_row(__("Inventory Account:"), 'default_inventory_act', $_POST['default_inventory_act']);
// this one is default for items and suppliers (purchase account)
gl_all_accounts_list_row(__("C.O.G.S. Account:"), 'default_cogs_act', $_POST['default_cogs_act']);

gl_all_accounts_list_row(__("Inventory Adjustments Account:"), 'default_adj_act', $_POST['default_adj_act']);

gl_all_accounts_list_row(__("WIP Account:"), 'default_wip_act', $_POST['default_wip_act']);

//----------------

table_section_title(__("Fixed Assets Defaults"));

gl_all_accounts_list_row(__("Loss On Asset Disposal Account:"), 'default_loss_on_asset_disposal_act', $_POST['default_loss_on_asset_disposal_act']);

array_selector_row (__("Depreciation Period:"), 'depreciation_period', $_POST['depreciation_period'], array(FA_MONTHLY => __("Monthly"), FA_YEARLY => __("Yearly")));

//----------------

table_section_title(__("Manufacturing Defaults"));

text_row(__("Work Order Required By After:"), 'default_workorder_required', $_POST['default_workorder_required'], 6, 6, '', "", __("days"));

//----------------

end_outer_table(1);

submit_center('submit', __("Update"), true, '', 'default');

end_form(2);

//-------------------------------------------------------------------------------------------------

end_page();

