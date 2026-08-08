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
//----------------------------------------------------------------------------------
//	FrontAccounting system transaction types
//
define('ST_JOURNAL',      \App\Foundation\Shared\Enum\SystemType::Journal->value);

define('ST_BANKPAYMENT',  \App\Foundation\Shared\Enum\SystemType::BankPayment->value);
define('ST_BANKDEPOSIT',  \App\Foundation\Shared\Enum\SystemType::BankDeposit->value);
define('ST_BANKTRANSFER', \App\Foundation\Shared\Enum\SystemType::BankTransfer->value);

define('ST_SALESINVOICE', \App\Foundation\Shared\Enum\SystemType::SalesInvoice->value);
define('ST_CUSTCREDIT',   \App\Foundation\Shared\Enum\SystemType::CustomerCredit->value);
define('ST_CUSTPAYMENT',  \App\Foundation\Shared\Enum\SystemType::CustomerPayment->value);
define('ST_CUSTDELIVERY', \App\Foundation\Shared\Enum\SystemType::CustomerDelivery->value);
define('ST_MKTCUSTPAYMENT', \App\Foundation\Shared\Enum\SystemType::MarketplaceCustomerPayment->value);
define('ST_MKTCUSTREFUND', \App\Foundation\Shared\Enum\SystemType::MarketplaceCustomerRefund->value);

define('ST_LOCTRANSFER',  \App\Foundation\Shared\Enum\SystemType::LocTransfer->value);
define('ST_INVADJUST',    \App\Foundation\Shared\Enum\SystemType::InvAdjust->value);

define('ST_PURCHORDER',   \App\Foundation\Shared\Enum\SystemType::PurchOrder->value);
define('ST_SUPPINVOICE',  \App\Foundation\Shared\Enum\SystemType::SupplierInvoice->value);
define('ST_SUPPCREDIT',   \App\Foundation\Shared\Enum\SystemType::SupplierCredit->value);
define('ST_SUPPAYMENT',   \App\Foundation\Shared\Enum\SystemType::SupplierPayment->value);
define('ST_SUPPRECEIVE',  \App\Foundation\Shared\Enum\SystemType::SupplierReceive->value);

define('ST_WORKORDER',    \App\Foundation\Shared\Enum\SystemType::WorkOrder->value);
define('ST_MANUISSUE',    \App\Foundation\Shared\Enum\SystemType::ManuIssue->value);
define('ST_MANURECEIVE',  \App\Foundation\Shared\Enum\SystemType::ManuReceive->value);

define('ST_SALESORDER',  \App\Foundation\Shared\Enum\SystemType::SalesOrder->value);
define('ST_SALESQUOTE',  \App\Foundation\Shared\Enum\SystemType::SalesQuote->value);
define('ST_COSTUPDATE',  \App\Foundation\Shared\Enum\SystemType::CostUpdate->value);
define('ST_DIMENSION',   \App\Foundation\Shared\Enum\SystemType::Dimension->value);
define('ST_CUSTOMER',    \App\Foundation\Shared\Enum\SystemType::Customer->value);
define('ST_SUPPLIER',    \App\Foundation\Shared\Enum\SystemType::Supplier->value);
define('ST_ITEM',        \App\Foundation\Shared\Enum\SystemType::Item->value);
define('ST_FIXEDASSET',  \App\Foundation\Shared\Enum\SystemType::FixedAsset->value);
define('ST_BANKACCOUNT', \App\Foundation\Shared\Enum\SystemType::BankAccount->value);

// Don't include these defines in the $systypes_array.
// They are used for documents only.
define('ST_STATEMENT', \App\Foundation\Shared\Enum\SystemType::Statement->value);
define('ST_CHEQUE',    \App\Foundation\Shared\Enum\SystemType::Cheque->value);

//
//	Depreciation period types
//
define('FA_MONTHLY', \App\Legacy\Enum\DepreciationPeriodType::MONTHLY->value);
define('FA_YEARLY', \App\Legacy\Enum\DepreciationPeriodType::YEARLY->value);

// Subledger types
define('SLT_MARKETPLACE', -2);
define('SLT_AP', -1);
define('SLT_AR', 1);

// document inheritance
$GLOBALS['document_child_types'] = array(
    ST_SALESQUOTE => ST_SALESORDER,
    ST_SALESORDER => ST_CUSTDELIVERY,
    ST_CUSTDELIVERY => ST_SALESINVOICE,
    ST_SALESINVOICE => ST_CUSTCREDIT,

    ST_PURCHORDER => ST_SUPPRECEIVE,
    ST_SUPPRECEIVE => ST_SUPPINVOICE,
    ST_SUPPINVOICE => ST_SUPPCREDIT,
);

function get_child_type($type)
{
	global $document_child_types;
	return isset($document_child_types[$type]) ? $document_child_types[$type] : 0;
}

function get_parent_type($type)
{
	global $document_child_types;
	$child = array_search($type, $document_child_types);
	return $child ? $child : 0;
}

//----------------------------------------------------------------------------------
//		Bank transaction types
//
define('BT_TRANSFER', 0);
define('BT_CHEQUE', 1);
define('BT_CREDIT', 2);
define('BT_CASH', 3);

require_once __DIR__ . "/../manufacturing/includes/manufacturing_db.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_db.inc";
require_once __DIR__ . "/../sales/includes/sales_db.inc";
require_once __DIR__ . "/../dimensions/includes/dimensions_db.inc";
//----------------------------------------------------------------------------------
//	Payment types
//
define('PT_MISC', 0);
define('PT_WORKORDER', 1);
define('PT_CUSTOMER', 2);
define('PT_SUPPLIER', 3);
define('PT_QUICKENTRY', 4);
define('PT_DIMESION', 5);
define('PT_MARKETPLACE', 6);

function payment_person_currency($type, $person_id)  {
	switch ($type)
	{
		case PT_MISC :
		case PT_QUICKENTRY :
		case PT_WORKORDER :
		case PT_MARKETPLACE :
			return get_company_currency();

		case PT_CUSTOMER :
			return get_customer_currency($person_id);

		case PT_SUPPLIER :
			return get_supplier_currency($person_id);

		default :
			return get_company_currency();
	}
}

function payment_person_name($type, $person_id, $full=true) {
	global $payment_person_types;

	switch ($type)
	{
		case PT_MISC :
			return $person_id;
		case PT_QUICKENTRY :
			$qe = get_quick_entry($person_id);
			return ($full ? $payment_person_types[$type] . " ":"") . $qe["description"];
		case PT_WORKORDER :
			global $wo_cost_types;
			return (!$full ? __('Work Order').' '.$person_id : get_trans_view_str(ST_WORKORDER, $person_id, __('Work Order').' '.$person_id));
		case PT_CUSTOMER :
			return ($full ?$payment_person_types[$type] . " ":"") . sprintf("[%05s] %s", $person_id, get_customer_name($person_id));
		case PT_SUPPLIER :
			return ($full ? $payment_person_types[$type] . " ":"") . sprintf("[%05s] %s", $person_id, get_supplier_name($person_id));
		case PT_MARKETPLACE :
			return ($full ? $payment_person_types[$type] . " ":"") . sprintf("[%05s] %s", $person_id, get_marketplace_name($person_id));
		default :
			return '';
	}
}

function payment_person_has_items($type) {
	switch ($type)
	{
		case PT_MISC :
			return true;
		case PT_QUICKENTRY :
			return db_has_quick_entries();
		case PT_WORKORDER : // 070305 changed to open workorders JH
			return db_has_open_workorders();
		case PT_CUSTOMER :
			return db_has_customers();
		case PT_SUPPLIER :
			return db_has_suppliers();
		case PT_MARKETPLACE :
			return db_has_marketplaces();
		default :
			display_db_error("Invalid type sent to has_items", "");
			return false;
	}
}
//----------------------------------------------------------------------------------
//	Payment terms categories
//
define('PM_ANY', 0);
define('PM_CASH', 1);
define('PM_CREDIT', 2);

//----------------------------------------------------------------------------------
//	Manufacturing types
//
define('WO_ASSEMBLY', 0);
define('WO_UNASSEMBLY', 1);
define('WO_ADVANCED', 2);

define('WO_LABOUR', 0);
define('WO_OVERHEAD', 1);
define('WO_MATERIALS', 2);

//----------------------------------------------------------------------------------
//	GL account classes
//
define('CL_NONE', 0); // for backward compatibility
define('CL_ASSETS', 1);
define('CL_LIABILITIES', 2);
define('CL_EQUITY', 3);
define('CL_INCOME', 4);
define('CL_COGS', 5);
define('CL_EXPENSE', 6);

function get_class_type_convert($ctype)
{
	global $SysPrefs;

	if (isset($SysPrefs->use_oldstyle_convert) && $SysPrefs->use_oldstyle_convert == 1)
		return (($ctype >= CL_INCOME || $ctype == CL_NONE) ? -1 : 1);
	else	
		return ((($ctype >= CL_LIABILITIES && $ctype <= CL_INCOME) || $ctype == CL_NONE) ? -1 : 1);
}
//----------------------------------------------------------------------------------
//	Quick entry types
//
define('QE_PAYMENT', '1');
define('QE_DEPOSIT', '2');
define('QE_JOURNAL', '3');
define('QE_SUPPINV', '4');

//----------------------------------------------------------------------------------
//	Special option values for various list selectors.
//
define('ANY_TEXT', '');
define('ANY_NUMERIC', -1);
define('ALL_TEXT', '');
define('ALL_NUMERIC', -1);

//----------------------------------------------------------------------------------
// Special class values for tables (start_table())
define('TABLESTYLE',  1);
define('TABLESTYLE2', 2);
define('TABLESTYLE_NOBORDER', 3);

//----------------------------------------------------------------------------------

define('TAG_ACCOUNT',   1);
define('TAG_DIMENSION', 2);

//----------------------------------------------------------------------------------
// Payment term types

define('PTT_PRE', 1);
define('PTT_CASH', 2);
define('PTT_DAYS', 3);
define('PTT_FOLLOWING', 4);

//----------------------------------------------------------------------------------
// Tax calculation algorithms used in als and purchase (depends on supplier's invoicing software)

define('TCA_TOTALS', 1); // taxes are calculated from respective net totals for all lines
define('TCA_LINES', 2); // taxes calculated for every line, then summed
//
//	Bank account owner types
//
define('BO_UNKNOWN', 0);
define('BO_COMPANY', 1);
define('BO_CUSTBRANCH', 2);
define('BO_SUPPLIER', 3);

require_once __DIR__ . '/../includes/sysnames.inc';

// tax register type
define('TR_OUTPUT', 0); // sales
define('TR_INPUT', 1);	// purchase
//---------------------------------------------------------------------------------
// Constants optionally redefined locally
//
defined('ICON_EDIT') || define('ICON_EDIT', 'edit.gif');
defined('ICON_DELETE') || define('ICON_DELETE', 'delete.gif');
defined('ICON_ADD')	|| define('ICON_ADD', 'ok.gif');
defined('ICON_UPDATE') || define('ICON_UPDATE', 'ok.gif');
defined('ICON_OK') || define('ICON_OK', 'ok.gif');
defined('ICON_CANCEL') || define('ICON_CANCEL', 'cancel.png');
defined('ICON_GL') || define('ICON_GL', 'gl.png');
defined('ICON_PRINT') || define('ICON_PRINT', 'print.png');
defined('ICON_PDF') || define('ICON_PDF', 'pdf.gif');
defined('ICON_DOC') || define('ICON_DOC', 'invoice.gif');
defined('ICON_CREDIT') || define('ICON_CREDIT', 'credit.gif');
defined('ICON_RECEIVE') || define('ICON_RECEIVE', 'receive.gif');
defined('ICON_DOWN') || define('ICON_DOWN', 'download.gif');
defined('ICON_MONEY') || define('ICON_MONEY', 'money.png');
defined('ICON_REMOVE') || define('ICON_REMOVE', 'remove.png');
defined('ICON_REPORT') || define('ICON_REPORT', 'report.png');
defined('ICON_VIEW') || define('ICON_VIEW', 'view.gif');
defined('ICON_SUBMIT') || define('ICON_SUBMIT', 'ok.gif');
defined('ICON_ESCAPE') || define('ICON_ESCAPE', 'escape.png');
defined('ICON_ALLOC') || define('ICON_ALLOC', 'alloc.png');
defined('ICON_CLOSED') || define('ICON_CLOSED', 'closed.png');

