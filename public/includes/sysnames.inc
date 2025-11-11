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
//	FrontAccounting global system names. They have to be stored separate from
//	sys types defines to enable inclusion after every language change
//	(which happens for multilanguage bulk reports)
//
global $systypes_array, $bank_account_types, $bank_transfer_types, 
	$payment_person_types, $wo_types_array, $wo_cost_types, $class_types,
	$quick_actions, $quick_entry_types, $stock_types, $tag_types,
    $bank_owner, $type_shortcuts, $tmonths, $pterm_types, $tax_algorithms,
    $supported_collations, $depreciation_methods, $fa_systypes_array;

$systypes_array = array (
	ST_JOURNAL => __("Journal Entry"),
	ST_BANKPAYMENT => __("Bank Payment"),
	ST_BANKDEPOSIT => __("Bank Deposit"),
	ST_BANKTRANSFER => __("Funds Transfer"),
	ST_SALESINVOICE => __("Sales Invoice"),
	ST_CUSTCREDIT => __("Customer Credit Note"),
	ST_CUSTPAYMENT => __("Customer Payment"),
	ST_CUSTDELIVERY => __("Delivery Note"),
	ST_LOCTRANSFER => __("Location Transfer"),
	ST_INVADJUST => __("Inventory Adjustment"),
	ST_PURCHORDER => __("Purchase Order"),
	ST_SUPPINVOICE => __("Supplier Invoice"),
	ST_SUPPCREDIT => __("Supplier Credit Note"),
	ST_SUPPAYMENT => __("Supplier Payment"),
	ST_SUPPRECEIVE => __("Purchase Order Delivery"),
	ST_WORKORDER => __("Work Order"),
	ST_MANUISSUE => __("Work Order Issue"),
	ST_MANURECEIVE => __("Work Order Production"),
	ST_SALESORDER => __("Sales Order"),
	ST_SALESQUOTE => __("Sales Quotation"),
	ST_COSTUPDATE => __("Cost Update"),
	ST_DIMENSION => __("Dimension"),
	ST_CUSTOMER => __("Customer"),
	ST_SUPPLIER => __("Supplier"),
	ST_ITEM => __("Item"),
	ST_FIXEDASSET => __("Fixed Asset"),
	ST_BANKACCOUNT => __("Bank Account"),
	);

$fa_systypes_array = array (
	ST_INVADJUST => __("Fixed Assets Disposal"),
	ST_COSTUPDATE => __("Fixed Assets Revaluation"),
	);

$type_shortcuts = array(
	ST_JOURNAL => __("GJ"), // general journal
	ST_BANKPAYMENT => __("BP"),
	ST_BANKDEPOSIT => __("BD"),
	ST_BANKTRANSFER => __("BT"),
	ST_SALESINVOICE => __("SI"),
	ST_CUSTCREDIT => __("CN"),
	ST_CUSTPAYMENT => __("CP"),
	ST_CUSTDELIVERY => __("DN"),
	ST_LOCTRANSFER => __("IT"), // inventory transfer
	ST_INVADJUST => __("IA"),
	ST_PURCHORDER => __("PO"),
	ST_SUPPINVOICE => __("PI"), // purchase invoice
	ST_SUPPCREDIT => __("PC"),
	ST_SUPPAYMENT => __("SP"),
	ST_SUPPRECEIVE => __("GRN"),
	ST_WORKORDER => __("WO"),
	ST_MANUISSUE => __("WI"),
	ST_MANURECEIVE => __("WP"),
	ST_SALESORDER => __("SO"),
	ST_SALESQUOTE => __("SQ"),
	ST_COSTUPDATE => __("CU"),
	ST_DIMENSION => __("Dim")
);


//----------------------------------------------------------------------------------
//		Bank transaction types
//
$bank_account_types = array (
	BT_TRANSFER => __("Savings Account"),
		__("Chequing Account"),
		__("Credit Account"),
		__("Cash Account")
	);

$bank_transfer_types = array(
	BT_TRANSFER => __("Transfer"),
			__("Cheque"),
			__("Credit"),
			__("Cash")
	);

//----------------------------------------------------------------------------------
//	Payment types
//

$payment_person_types = array (
	PT_MISC => __("Miscellaneous"),
    PT_WORKORDER =>	__("Work Order"),
	PT_CUSTOMER => __("Customer"),
	PT_SUPPLIER => __("Supplier"),
	PT_QUICKENTRY => __("Quick Entry"),
	PT_MARKETPLACE => __("Marketplace"),
);

//----------------------------------------------------------------------------------
//	Manufacturing types
//
$wo_types_array = array (
	WO_ASSEMBLY => __("Assemble"),
	WO_UNASSEMBLY => __("Unassemble"),
	WO_ADVANCED => __("Advanced Manufacture")
	);

$wo_cost_types = array(
	WO_LABOUR => __("Labour Cost"),
	WO_OVERHEAD => __("Overhead Cost"),
	WO_MATERIALS => __("Materials")
);

//----------------------------------------------------------------------------------
//	GL account classes
//
$class_types = array(
	CL_ASSETS => __("Assets"),
	CL_LIABILITIES => __("Liabilities"),
	CL_EQUITY => __("Equity"),
	CL_INCOME => __("Income"),
	CL_COGS => __("Cost of Goods Sold"),
	CL_EXPENSE => __("Expense"),
);

//----------------------------------------------------------------------------------
//	Quick entry types
//
$quick_actions = array(
	'=' => __('Remainder'), // post current base amount to GL account
	'a' => __('Amount'), // post amount to GL account
	'a+' => __('Amount, increase base'), // post amount to GL account and increase base
	'a-' => __('Amount, reduce base'), // post amount to GL account and reduce base
	'%' => __('% amount of base'),	// store acc*amount% to GL account
	'%+' => __('% amount of base, increase base'),	// ditto & increase base amount
	'%-' => __('% amount of base, reduce base'),	// ditto & reduce base amount
	'T' => __('Taxes added'), // post taxes calculated on base amount
	'T+' => __('Taxes added, increase base'), // ditto & increase base amount
	'T-' => __('Taxes added, reduce base'), // ditto & reduce base amount
	't' => __('Taxes included'), // post taxes calculated on base amount
	't+' => __('Taxes included, increase base'), // ditto & increase base amount
	't-' => __('Taxes included, reduce base') // ditto & reduce base amount
);

$quick_entry_types = array(
	QE_DEPOSIT => __("Bank Deposit"),
	QE_PAYMENT => __("Bank Payment"),
	QE_JOURNAL => __("Journal Entry"),
	QE_SUPPINV => __("Supplier Invoice/Credit")
);

//----------------------------------------------------------------------------------
// depreciation methods
$depreciation_methods = array(
  'D' => __("Declining balance"),
  'S' => __("Straight line"),
  'N' => __("Sum of the Year Digits"),
  'O' => __("One-time"),
);

//----------------------------------------------------------------------------------
// Types of stock items
$stock_types = array(
	'M' => __("Manufactured"),
	'B' => __("Purchased"),
	'D' => __("Service")
);

//----------------------------------------------------------------------------------

$tag_types = array (
	TAG_ACCOUNT   => __("Account"),
	TAG_DIMENSION => __("Dimension")
);

//----------------------------------------------------------------------------------
// crm contacts categories
$sys_crm_cats = array (
	'cust_branch' => __("Customer branch"),
	'supplier' => __("Supplier"),
	'shipper' => __("Shipper"),
	'company' => __("Company internal")
);
//----------------------------------------------------------------------------------

$pterm_types = array(
	PTT_PRE => __("Prepayment"),
	PTT_CASH => __("Cash"),
	PTT_DAYS => __("After No. of Days"),
	PTT_FOLLOWING => __("Day In Following Month")
);

$tax_algorithms = array( 
	TCA_LINES => __("Sum per line taxes"), 
	TCA_TOTALS => __("Taxes from totals")
);
//----------------------------------------------------------------------------------

$bank_owner_types = array(
	BO_UNKNOWN => __("Unknown"),
	BO_COMPANY => __("Company"),
	BO_CUSTBRANCH => __("Customer"),
	BO_SUPPLIER => __("Supplier")
);

// This month array is for use with the last 3 dateformats. 
$tmonths = array("", __("Jan"),__("Feb"),__("Mar"),__("Apr"),__("May"),__("Jun"),__("Jul"),__("Aug"),__("Sep"),__("Oct"),__("Nov"),__("Dec"));

//
//	FA supported db backend language settings.
//
$supported_collations = array(
  'utf8_xx' => __('Unicode (multilanguage)'),
  'utf8_is' => __('Icelandic'),
  'utf8_lv' => __('Latvian'),
  'utf8_ro' => __('Romanian'),
  'utf8_sl' => __('Slovenian'),
  'utf8_pl' => __('Polish'),
  'utf8_et' => __('Estonian'),
  'utf8_es' => __('Spanish'), // or 'spanish2',
  'utf8_sw' => __('Swedish'),
  'utf8_tr' => __('Turkish'),
  'utf8_cs' => __('Czech'),
  'utf8_da' => __('Danish'),
  'utf8_lt' => __('Lithuanian'),
  'utf8_sk' => __('Slovak'),
  'utf8_sp' => __('Spanish (alternative)'),
  'utf8_fa' => __('Persian'),
  'utf8_hu' => __('Hungarian'),
  'utf8_fr' => __('French'),
  'utf8_it' => __('Italian'),
);

