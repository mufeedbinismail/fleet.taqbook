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

$GLOBALS['page_security'] = Permission::CREATE_PURCHASE_PAYMENT;
require_once __DIR__ . "/../includes/ui/allocation_cart.inc";
require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../includes/date_functions.inc";
require_once __DIR__ . "/../includes/ui.inc";
require_once __DIR__ . "/../includes/banking.inc";
require_once __DIR__ . "/../includes/data_checks.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_db.inc";
require_once __DIR__ . "/../reporting/includes/reporting.inc";

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

add_js_file('payalloc.js');

page(__($GLOBALS['help_context'] = "Supplier Payment Entry"), false, false, "", $js);

if (isset($_GET['supplier_id']))
{
	$_POST['supplier_id'] = $_GET['supplier_id'];
}

//----------------------------------------------------------------------------------------

check_db_has_suppliers(__("There are no suppliers defined in the system."));

check_db_has_bank_accounts(__("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------

if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier(false);

if (!isset($_POST['DatePaid']))
{
	$_POST['DatePaid'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['DatePaid']))
		$_POST['DatePaid'] = end_fiscalyear();
}

if (isset($_POST['_DatePaid_changed'])) {
  $Ajax->activate('_ex_rate');
}

//----------------------------------------------------------------------------------------

if (!isset($_POST['bank_account'])) { // first page call
	$_SESSION['alloc'] = new allocation(ST_SUPPAYMENT, 0, get_post('supplier_id'));

	if (isset($_GET['PInvoice'])) {
		$supp = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
		//  get date and supplier
		$inv = get_supp_trans($_GET['PInvoice'], $_GET['trans_type'], $supp);
		if ($inv) {
			$_SESSION['alloc']->person_id = $_POST['supplier_id'] = $inv['supplier_id'];
			$_SESSION['alloc']->read();
			$_POST['DatePaid'] = sql2date($inv['tran_date']);
			$_POST['memo_'] = $inv['supp_reference'];
			foreach($_SESSION['alloc']->allocs as $line => $trans) {
				if ($trans->type == $_GET['trans_type'] && $trans->type_no == $_GET['PInvoice']) {
					$un_allocated = abs($trans->amount) - $trans->amount_allocated;
					$_SESSION['alloc']->amount = $_SESSION['alloc']->allocs[$line]->current_allocated = $un_allocated;
					$_POST['amount'] = $_POST['amount'.$line] = price_format($un_allocated);
					break;
				}
			}
			unset($inv);
		} else
			display_error(__("Invalid purchase invoice number."));
	}
}
if (isset($_GET['AddedID'])) {
	$payment_id = $_GET['AddedID'];

   	display_notification_centered( __("Payment has been sucessfully entered"));

	submenu_print(__("&Print This Remittance"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, 'prtopt');
	submenu_print(__("&Email This Remittance"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, null, 1);

	submenu_view(__("View this Payment"), ST_SUPPAYMENT, $payment_id);
    display_note(get_gl_view_str(ST_SUPPAYMENT, $payment_id, __("View the GL &Journal Entries for this Payment")), 0, 1);

	submenu_option(__("Enter another supplier &payment"), "/purchasing/supplier_payment.php?supplier_id=".$_POST['supplier_id']);

	submenu_option(__("Enter &Supplier Invoice"), "/purchasing/supplier_invoice.php?New=1");
	submenu_option(__("Enter direct &Invoice"), "/purchasing/po_entry_items.php?NewInvoice=Yes");

	submenu_option(__("Enter Other &Payment"), "/gl/gl_bank.php?NewPayment=Yes");
	submenu_option(__("Enter &Customer Payment"), "/sales/customer_payments.php");
	submenu_option(__("Enter Other &Deposit"), "/gl/gl_bank.php?NewDeposit=Yes");
	submenu_option(__("Bank Account &Transfer"), "/gl/bank_transfer.php");
	submenu_option(__("Add an Attachment"), "/admin/attachments.php?filterType=".ST_SUPPAYMENT."&trans_no=$payment_id");

	display_footer_exit();
}

//----------------------------------------------------------------------------------------

function get_default_supplier_payment_bank_account($supplier_id, $date)
{
	$previous_payment = get_supp_payment_before($supplier_id, date2sql($date));
	if ($previous_payment)
	{
		return $previous_payment['bank_id'];
	}
	return get_default_supplier_bank_account($supplier_id);
}
//----------------------------------------------------------------------------------------

function check_inputs()
{
	global $Refs;

	if (!get_post('supplier_id')) 
	{
		display_error(__("There is no supplier selected."));
		set_focus('supplier_id');
		return false;
	} 
	
	if (@$_POST['amount'] == "") 
	{
		$_POST['amount'] = price_format(0);
	}

	if (!check_num('amount', 0))
	{
		display_error(__("The entered amount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}

	if (isset($_POST['charge']) && !check_num('charge', 0)) {
		display_error(__("The entered amount is invalid or less than zero."));
		set_focus('charge');
		return false;
	}

	if (isset($_POST['charge']) && input_num('charge') > 0) {
		$charge_acct = get_bank_charge_account($_POST['bank_account']);
		if (get_gl_account($charge_acct) == false) {
			display_error(__("The Bank Charge Account has not been set in System and General GL Setup."));
			set_focus('charge');
			return false;
		}	
	}

	if (@$_POST['discount'] == "") 
	{
		$_POST['discount'] = 0;
	}

	if (!check_num('discount', 0))
	{
		display_error(__("The entered discount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}

	//if (input_num('amount') - input_num('discount') <= 0) 
	if (input_num('amount') <= 0) 
	{
		display_error(__("The total of the amount and the discount is zero or negative. Please enter positive values."));
		set_focus('amount');
		return false;
	}

	if (isset($_POST['bank_amount']) && input_num('bank_amount')<=0)
	{
		display_error(__("The entered bank amount is zero or negative."));
		set_focus('bank_amount');
		return false;
	}


   	if (!is_date($_POST['DatePaid']))
   	{
		display_error(__("The entered date is invalid."));
		set_focus('DatePaid');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['DatePaid'])) 
	{
		display_error(__("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('DatePaid');
		return false;
	}

	$limit = get_bank_account_limit($_POST['bank_account'], $_POST['DatePaid']);

	if (($limit !== null) && (floatcmp($limit, input_num('amount')) < 0))
	{
		display_error(sprintf(__("The total bank amount exceeds allowed limit (%s)."), price_format($limit)));
		set_focus('amount');
		return false;
	}

	if (!check_reference($_POST['ref'], ST_SUPPAYMENT))
	{
		set_focus('ref');
		return false;
	}

	if (!db_has_currency_rates(get_supplier_currency($_POST['supplier_id']), $_POST['DatePaid'], true))
		return false;

	$_SESSION['alloc']->amount = -input_num('amount');

	if (isset($_POST["TotalNumberOfAllocs"]))
		return check_allocations();
	else
		return true;
}

//----------------------------------------------------------------------------------------

function handle_add_payment()
{
	$payment_id = write_supp_payment(0, $_POST['supplier_id'], $_POST['bank_account'],
		$_POST['DatePaid'], $_POST['ref'], input_num('amount'),	input_num('discount'), $_POST['memo_'], 
		input_num('charge'), input_num('bank_amount', input_num('amount')), $_POST['dimension_id'], $_POST['dimension2_id']);
	new_doc_date($_POST['DatePaid']);

	$_SESSION['alloc']->trans_no = $payment_id;
	$_SESSION['alloc']->date_ = $_POST['DatePaid'];
	$_SESSION['alloc']->write();

   	unset($_POST['bank_account']);
   	unset($_POST['DatePaid']);
   	unset($_POST['currency']);
   	unset($_POST['memo_']);
   	unset($_POST['amount']);
   	unset($_POST['discount']);
   	unset($_POST['ProcessSuppPayment']);

	meta_forward(url()->current(), "AddedID=$payment_id&supplier_id=".$_POST['supplier_id']);
}

//----------------------------------------------------------------------------------------

if (isset($_POST['ProcessSuppPayment']))
{
	 /*First off  check for valid inputs */
    if (check_inputs() == true) 
    {
    	handle_add_payment();
    	end_page();
     	throw new \App\Legacy\Exception\FlowCompletedException;
    }
}

//----------------------------------------------------------------------------------------

start_form();

	start_outer_table(TABLESTYLE2, "width='60%'", 5);

	table_section(1);

    supplier_list_row(__("Payment To:"), 'supplier_id', null, false, true);

	if (list_updated('supplier_id')) {
		$_POST['amount'] = price_format(0);
		$_SESSION['alloc']->person_id = get_post('supplier_id');
		$Ajax->activate('amount');
	} elseif (list_updated('bank_account'))
		$Ajax->activate('alloc_tbl');

	if (list_updated('supplier_id') || list_updated('bank_account')) {
	  $_SESSION['alloc']->read();
	  $_POST['memo_'] = $_POST['amount'] = '';
	  $Ajax->activate('alloc_tbl');
	}

	set_global_supplier($_POST['supplier_id']);

	if (!list_updated('bank_account') && !get_post('__ex_rate_changed'))
	{
		$_POST['bank_account'] = get_default_supplier_payment_bank_account($_POST['supplier_id'], $_POST['DatePaid']);
	} else
	{
		$_POST['amount'] = price_format(0);
	}

    bank_accounts_list_row(__("From Bank Account:"), 'bank_account', null, true);

	bank_balance_row($_POST['bank_account']);

	table_section(2);

    date_row(__("Date Paid") . ":", 'DatePaid', '', true, 0, 0, 0, null, true);

    ref_row(__("Reference:"), 'ref', '', $Refs->get_next(ST_SUPPAYMENT, null, 
    	array('supplier'=>get_post('supplier_id'), 'date'=>get_post('DatePaid'))), false, ST_SUPPAYMENT);


	table_section(3);

	$comp_currency = get_company_currency();
	$supplier_currency = $_SESSION['alloc']->set_person($_POST['supplier_id'], PT_SUPPLIER);
	if (!$supplier_currency)
			$supplier_currency = $comp_currency;
	$_SESSION['alloc']->currency = $bank_currency = get_bank_account_currency($_POST['bank_account']);

	if ($bank_currency != $supplier_currency) 
	{
		amount_row(__("Bank Amount:"), 'bank_amount', null, '', $bank_currency);
	}

	amount_row(__("Bank Charge:"), 'charge', null, '', $bank_currency);

	$row = get_supplier($_POST['supplier_id']);
	$_POST['dimension_id'] = @$row['dimension_id'];
	$_POST['dimension2_id'] = @$row['dimension2_id'];
	$dim = get_company_pref('use_dimension');
	if ($dim > 0)
		dimensions_list_row(__("Dimension").":", 'dimension_id',
			null, true, ' ', false, 1, false);
	else
		hidden('dimension_id', 0);
	if ($dim > 1)
		dimensions_list_row(__("Dimension")." 2:", 'dimension2_id',
			null, true, ' ', false, 2, false);
	else
		hidden('dimension2_id', 0);

	end_outer_table(1);

	div_start('alloc_tbl');
	show_allocatable(false);
	div_end();

	start_table(TABLESTYLE, "width='60%'");
	amount_row(__("Amount of Discount:"), 'discount', null, '', $supplier_currency);
	amount_row(__("Amount of Payment:"), 'amount', null, '', $supplier_currency);
	textarea_row(__("Memo:"), 'memo_', null, 22, 4);
	end_table(1);

	submit_center('ProcessSuppPayment',__("Enter Payment"), true, '', 'default');

end_form();

end_page();
