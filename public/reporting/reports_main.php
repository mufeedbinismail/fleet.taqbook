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

$GLOBALS['page_security'] = Permission::OPEN;
require_once __DIR__ . "/../includes/session.inc";

require_once __DIR__ . "/../includes/date_functions.inc";
require_once __DIR__ . "/../includes/data_checks.inc";
require_once __DIR__ . "/../includes/ui.inc";
require_once __DIR__ . "/../reporting/includes/reports_classes.inc";
$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

add_js_file('reports.js');

page(__($GLOBALS['help_context'] = "Reports and Analysis"), false, false, "", $js);

$reports = new BoxReports;

$dim = get_company_pref('use_dimension');

$reports->addReportClass(__('Customer'), RC_CUSTOMER);
$reports->addReport(RC_CUSTOMER, 101, __('Customer &Balances'),
	array(	__('Start Date') => 'DATEBEGIN',
			__('End Date') => 'DATEENDM',
			__('Customer') => 'CUSTOMERS_NO_FILTER',
			__('Show Balance') => 'YES_NO',
			__('Currency Filter') => 'CURRENCY',
			__('Suppress Zeros') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 102, __('&Aged Customer Analysis'),
	array(	__('End Date') => 'DATE',
			__('Customer') => 'CUSTOMERS_NO_FILTER',
			__('Currency Filter') => 'CURRENCY',
			__('Show Also Allocated') => 'YES_NO',
			__('Summary Only') => 'YES_NO',
			__('Suppress Zeros') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 115, __('Customer Trial Balance'),
    array(  __('Start Date') => 'DATEBEGIN',
            __('End Date') => 'DATEENDM',
            __('Customer') => 'CUSTOMERS_NO_FILTER',
            __('Sales Areas') => 'AREAS',
            __('Sales Folk') => 'SALESMEN',
            __('Currency Filter') => 'CURRENCY',
            __('Suppress Zeros') => 'YES_NO',
            __('Comments') => 'TEXTBOX',
            __('Orientation') => 'ORIENTATION',
            __('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 103, __('Customer &Detail Listing'),
	array(	__('Activity Since') => 'DATEBEGIN',
			__('Sales Areas') => 'AREAS',
			__('Sales Folk') => 'SALESMEN',
			__('Activity Greater Than') => 'TEXT',
			__('Activity Less Than') => 'TEXT',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 114, __('Sales &Summary Report'),
	array(	__('Start Date') => 'DATEBEGINTAX',
			__('End Date') => 'DATEENDTAX',
			__('Tax Id Only') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 104, __('&Price Listing'),
	array(	__('Currency Filter') => 'CURRENCY',
			__('Inventory Category') => 'CATEGORIES',
			__('Sales Types') => 'SALESTYPES',
			__('Show Pictures') => 'YES_NO',
			__('Show GP %') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 105, __('&Order Status Listing'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Inventory Category') => 'CATEGORIES',
			__('Stock Location') => 'LOCATIONS',
			__('Back Orders Only') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 106, __('&Salesman Listing'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Summary Only') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_CUSTOMER, 107, __('Print &Invoices'),
	array(	__('From') => 'INVOICE',
			__('To') => 'INVOICE',
			__('Currency Filter') => 'CURRENCY',
			__('email Customers') => 'YES_NO',
			__('Payment Link') => 'PAYMENT_LINK',
			__('Comments') => 'TEXTBOX',
			__('Customer') => 'CUSTOMERS_NO_FILTER',
			__('Orientation') => 'ORIENTATION'
));
$reports->addReport(RC_CUSTOMER, 113, __('Print &Credit Notes'),
	array(	__('From') => 'CREDIT',
			__('To') => 'CREDIT',
			__('Currency Filter') => 'CURRENCY',
			__('email Customers') => 'YES_NO',
			__('Payment Link') => 'PAYMENT_LINK',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));
$reports->addReport(RC_CUSTOMER, 110, __('Print &Deliveries'),
	array(	__('From') => 'DELIVERY',
			__('To') => 'DELIVERY',
			__('email Customers') => 'YES_NO',
			__('Print as Packing Slip') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));
$reports->addReport(RC_CUSTOMER, 108, __('Print &Statements'),
	array(	__('Customer') => 'CUSTOMERS_NO_FILTER',
			__('Currency Filter') => 'CURRENCY',
			__('Show Also Allocated') => 'YES_NO',
			__('Email Customers') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));
$reports->addReport(RC_CUSTOMER, 109, __('&Print Sales Orders'),
	array(	__('From') => 'ORDERS',
			__('To') => 'ORDERS',
			__('Currency Filter') => 'CURRENCY',
			__('Email Customers') => 'YES_NO',
			__('Print as Quote') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));
$reports->addReport(RC_CUSTOMER, 111, __('&Print Sales Quotations'),
	array(	__('From') => 'QUOTATIONS',
			__('To') => 'QUOTATIONS',
			__('Currency Filter') => 'CURRENCY',
			__('Email Customers') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));
$reports->addReport(RC_CUSTOMER, 112, __('Print Receipts'),
	array(	__('From') => 'RECEIPT',
			__('To') => 'RECEIPT',
			__('Currency Filter') => 'CURRENCY',
            __('Email Customers') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));

$reports->addReportClass(__('Supplier'), RC_SUPPLIER);
$reports->addReport(RC_SUPPLIER, 201, __('Supplier &Balances'),
	array(	__('Start Date') => 'DATEBEGIN',
			__('End Date') => 'DATEENDM',
			__('Supplier') => 'SUPPLIERS_NO_FILTER',
			__('Show Balance') => 'YES_NO',
			__('Currency Filter') => 'CURRENCY',
			__('Suppress Zeros') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_SUPPLIER, 202, __('&Aged Supplier Analyses'),
	array(	__('End Date') => 'DATE',
			__('Supplier') => 'SUPPLIERS_NO_FILTER',
			__('Currency Filter') => 'CURRENCY',
			__('Show Also Allocated') => 'YES_NO',
			__('Summary Only') => 'YES_NO',
			__('Suppress Zeros') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_SUPPLIER, 206, __('Supplier &Trial Balances'),
    array(  __('Start Date') => 'DATEBEGIN',
            __('End Date') => 'DATEENDM',
            __('Supplier') => 'SUPPLIERS_NO_FILTER',
            __('Currency Filter') => 'CURRENCY',
            __('Suppress Zeros') => 'YES_NO',
            __('Comments') => 'TEXTBOX',
            __('Orientation') => 'ORIENTATION',
            __('Destination') => 'DESTINATION'));
$reports->addReport(RC_SUPPLIER, 203, __('&Payment Report'),
	array(	__('End Date') => 'DATE',
			__('Supplier') => 'SUPPLIERS_NO_FILTER',
			__('Currency Filter') => 'CURRENCY',
			__('Suppress Zeros') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_SUPPLIER, 204, __('Outstanding &GRNs Report'),
	array(	__('Supplier') => 'SUPPLIERS_NO_FILTER',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_SUPPLIER, 205, __('Supplier &Detail Listing'),
	array(	__('Activity Since') => 'DATEBEGIN',
			__('Activity Greater Than') => 'TEXT',
			__('Activity Less Than') => 'TEXT',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_SUPPLIER, 209, __('Print Purchase &Orders'),
	array(	__('From') => 'PO',
			__('To') => 'PO',
			__('Currency Filter') => 'CURRENCY',
			__('Email Suppliers') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));
$reports->addReport(RC_SUPPLIER, 210, __('Print Remi&ttances'),
	array(	__('From') => 'REMITTANCE',
			__('To') => 'REMITTANCE',
			__('Currency Filter') => 'CURRENCY',
			__('Email Suppliers') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION'));

$reports->addReportClass(__('Inventory'), RC_INVENTORY);
$reports->addReport(RC_INVENTORY,  301, __('Inventory &Valuation Report'),
	array(	__('End Date') => 'DATE',
			__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Summary Only') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_INVENTORY,  302, __('Inventory &Planning Report'),
	array(	__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_INVENTORY, 303, __('Stock &Check Sheets'),
	array(	__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Show Pictures') => 'YES_NO',
			__('Inventory Column') => 'YES_NO',
			__('Show Only Shortages') => 'YES_NO',
			__('Suppress Zeros') => 'YES_NO',
			__('Item Like') => 'TEXT',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_INVENTORY, 304, __('Inventory &Sales Report'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Customer') => 'CUSTOMERS_NO_FILTER',
			__('Show Service Items') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_INVENTORY, 305, __('&GRN Valuation Report'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_INVENTORY, 306, __('Inventory P&urchasing Report'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Supplier') => 'SUPPLIERS_NO_FILTER',
			__('Items') => 'ITEMS_P',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_INVENTORY, 307, __('Inventory &Movement Report'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));				
$reports->addReport(RC_INVENTORY, 308, __('C&osted Inventory Movement Report'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));				
$reports->addReport(RC_INVENTORY, 309,__('Item &Sales Summary Report'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Inventory Category') => 'CATEGORIES',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));				
$reports->addReport(RC_INVENTORY, 310, __('Inventory Purchasing - &Transaction Based'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Inventory Category') => 'CATEGORIES',
			__('Location') => 'LOCATIONS',
			__('Supplier') => 'SUPPLIERS_NO_FILTER',
			__('Items') => 'ITEMS_P',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
if (get_company_pref('use_manufacturing'))
{
	$reports->addReportClass(__('Manufacturing'), RC_MANUFACTURE);
	$reports->addReport(RC_MANUFACTURE, 401, __('&Bill of Material Listing'),
		array(	__('From product') => 'ITEMS',
				__('To product') => 'ITEMS',
				__('Comments') => 'TEXTBOX',
				__('Orientation') => 'ORIENTATION',
				__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_MANUFACTURE, 402, __('Work Order &Listing'),
		array(	__('Items') => 'ITEMS_ALL',
				__('Location') => 'LOCATIONS',
				__('Outstanding Only') => 'YES_NO',
				__('Show GL Rows') => 'YES_NO',
				__('Comments') => 'TEXTBOX',
				__('Orientation') => 'ORIENTATION',
				__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_MANUFACTURE, 409, __('Print &Work Orders'),
		array(	__('From') => 'WORKORDER',
				__('To') => 'WORKORDER',
				__('Email Locations') => 'YES_NO',
				__('Comments') => 'TEXTBOX',
				__('Orientation') => 'ORIENTATION'));
}
if (get_company_pref('use_fixed_assets'))
{
	$reports->addReportClass(__('Fixed Assets'), RC_FIXEDASSETS);
	$reports->addReport(RC_FIXEDASSETS, 451, __('&Fixed Assets Valuation'),
		array(	__('End Date') => 'DATE',
				__('Fixed Assets Class') => 'FCLASS',
				__('Fixed Assets Location') => 'FLOCATIONS',
				__('Summary Only') => 'YES_NO',
				__('Comments') => 'TEXTBOX',
				__('Orientation') => 'ORIENTATION',
				__('Destination') => 'DESTINATION'));
}				
$reports->addReportClass(__('Dimensions'), RC_DIMENSIONS);
if ($dim > 0)
{
	$reports->addReport(RC_DIMENSIONS, 501, __('Dimension &Summary'),
	array(	__('From Dimension') => 'DIMENSION',
			__('To Dimension') => 'DIMENSION',
			__('Show Balance') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
}
$reports->addReportClass(__('Banking'), RC_BANKING);
	$reports->addReport(RC_BANKING,  601, __('Bank &Statement'),
	array(	__('Bank Accounts') => 'BANK_ACCOUNTS_NO_FILTER',
			__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Zero values') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_BANKING,  602, __('Bank Statement w/ &Reconcile'),
	array(	__('Bank Accounts') => 'BANK_ACCOUNTS',
			__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Comments') => 'TEXTBOX',
			__('Destination') => 'DESTINATION'));

$reports->addReportClass(__('General Ledger'), RC_GL);
$reports->addReport(RC_GL, 701, __('Chart of &Accounts'),
	array(	__('Show Balances') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_GL, 702, __('List of &Journal Entries'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Type') => 'SYS_TYPES',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));

if ($dim == 2)
{
	$reports->addReport(RC_GL, 704, __('GL Account &Transactions'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('From Account') => 'GL_ACCOUNTS',
			__('To Account') => 'GL_ACCOUNTS',
			__('Dimension')." 1" =>  'DIMENSIONS1',
			__('Dimension')." 2" =>  'DIMENSIONS2',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 705, __('Annual &Expense Breakdown'),
	array(	__('Year') => 'TRANS_YEARS',
			__('Dimension')." 1" =>  'DIMENSIONS1',
			__('Dimension')." 2" =>  'DIMENSIONS2',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Amounts in thousands') => 'YES_NO',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 706, __('&Balance Sheet'),
	array(	__('Start Date') => 'DATEBEGIN',
			__('End Date') => 'DATEENDM',
			__('Dimension')." 1" => 'DIMENSIONS1',
			__('Dimension')." 2" => 'DIMENSIONS2',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Decimal values') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 707, __('&Profit and Loss Statement'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Compare to') => 'COMPARE',
			__('Dimension')." 1" =>  'DIMENSIONS1',
			__('Dimension')." 2" =>  'DIMENSIONS2',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Decimal values') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 708, __('Trial &Balance'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Zero values') => 'YES_NO',
			__('Only balances') => 'YES_NO',
			__('Dimension')." 1" =>  'DIMENSIONS1',
			__('Dimension')." 2" =>  'DIMENSIONS2',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
}
elseif ($dim == 1)
{
	$reports->addReport(RC_GL, 704, __('GL Account &Transactions'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('From Account') => 'GL_ACCOUNTS',
			__('To Account') => 'GL_ACCOUNTS',
			__('Dimension') =>  'DIMENSIONS1',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 705, __('Annual &Expense Breakdown'),
	array(	__('Year') => 'TRANS_YEARS',
			__('Dimension') =>  'DIMENSIONS1',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Amounts in thousands') => 'YES_NO',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 706, __('&Balance Sheet'),
	array(	__('Start Date') => 'DATEBEGIN',
			__('End Date') => 'DATEENDM',
			__('Dimension') => 'DIMENSIONS1',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Decimal values') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 707, __('&Profit and Loss Statement'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Compare to') => 'COMPARE',
			__('Dimension') => 'DIMENSIONS1',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Decimal values') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 708, __('Trial &Balance'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Zero values') => 'YES_NO',
			__('Only balances') => 'YES_NO',
			__('Dimension') => 'DIMENSIONS1',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
}
else
{
	$reports->addReport(RC_GL, 704, __('GL Account &Transactions'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('From Account') => 'GL_ACCOUNTS',
			__('To Account') => 'GL_ACCOUNTS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 705, __('Annual &Expense Breakdown'),
	array(	__('Year') => 'TRANS_YEARS',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Amounts in thousands') => 'YES_NO',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 706, __('&Balance Sheet'),
	array(	__('Start Date') => 'DATEBEGIN',
			__('End Date') => 'DATEENDM',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Decimal values') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 707, __('&Profit and Loss Statement'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Compare to') => 'COMPARE',
			__('Account Tags') =>  'ACCOUNTTAGS',
			__('Decimal values') => 'YES_NO',
			__('Graphics') => 'GRAPHIC',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 708, __('Trial &Balance'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Zero values') => 'YES_NO',
			__('Only balances') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
}
$reports->addReport(RC_GL, 709, __('Ta&x Report'),
	array(	__('Start Date') => 'DATEBEGINTAX',
			__('End Date') => 'DATEENDTAX',
			__('Summary Only') => 'YES_NO',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));
$reports->addReport(RC_GL, 710, __('Audit Trail'),
	array(	__('Start Date') => 'DATEBEGINM',
			__('End Date') => 'DATEENDM',
			__('Type') => 'SYS_TYPES_ALL',
			__('User') => 'USERS',
			__('Comments') => 'TEXTBOX',
			__('Orientation') => 'ORIENTATION',
			__('Destination') => 'DESTINATION'));

add_custom_reports($reports);

echo $reports->getDisplay();

end_page();
