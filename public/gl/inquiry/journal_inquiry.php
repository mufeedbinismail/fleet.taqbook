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

$GLOBALS['page_security'] = 'SA_GLANALYTIC';

require_once __DIR__ . "/../../includes/db_pager.inc";
require_once __DIR__ . "/../../includes/session.inc";

require_once __DIR__ . "/../../includes/date_functions.inc";
require_once __DIR__ . "/../../includes/ui.inc";
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(__($GLOBALS['help_context'] = "Journal Inquiry"), false, false, "", $js);

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('Search'))
{
	$Ajax->activate('journal_tbl');
}
//--------------------------------------------------------------------------------------
if (!isset($_POST['filterType']))
	$_POST['filterType'] = -1;

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(__("Reference:"), 'Ref', '',null, __('Enter reference fragment or leave empty'));

journal_types_list_cells(__("Type:"), "filterType");
date_cells(__("From:"), 'FromDate', '', null, -user_transaction_days());
date_cells(__("To:"), 'ToDate');

end_row();
start_row();
ref_cells(__("Memo:"), 'Memo', '',null, __('Enter memo fragment or leave empty'));
users_list_cells(__("User:"), 'userid', null, false);
if (get_company_pref('use_dimension') && isset($_POST['dimension'])) // display dimension only, when started in dimension mode
	dimensions_list_cells(__('Dimension:'), 'dimension', null, true, null, true);
check_cells( __("Show closed:"), 'AlsoClosed', null);
submit_cells('Search', __("Search"), '', '', 'default');
end_row();
end_table();

function journal_pos($row)
{
	return $row['gl_seq'] ? $row['gl_seq'] : '-';
}

function systype_name($dummy, $type)
{
	global $systypes_array;
	
	return $systypes_array[$type];
}

function person_link($row) 
{
    return payment_person_name($row["person_type_id"],$row["person_id"]);
}

function view_link($row) 
{
	return get_trans_view_str($row["trans_type"], $row["trans_no"]);
}

function gl_link($row) 
{
	return get_gl_view_str($row["trans_type"], $row["trans_no"]);
}

function edit_link($row)
{

	$ok = true;
	if ($row['trans_type'] == ST_SALESINVOICE)
	{
		$myrow = get_customer_trans($row["trans_no"], $row["trans_type"]);
		if ($myrow['alloc'] != $myrow['Total'] || get_voided_entry(ST_SALESINVOICE, $row["trans_no"]) !== false)
			$ok = false;
	}
	
	return $ok ? trans_editor_link( $row["trans_type"], $row["trans_no"]) : '--';
}

function invoice_supp_reference($row)
{
	return $row['supp_reference'];
}

$sql = get_sql_for_journal_inquiry(get_post('filterType', -1), get_post('FromDate'),
	get_post('ToDate'), get_post('Ref'), get_post('Memo'), check_value('AlsoClosed'), get_post('userid'));

$cols = array(
	__("#") => array('fun'=>'journal_pos', 'align'=>'center'), 
	__("Date") =>array('name'=>'tran_date','type'=>'date','ord'=>'desc'),
	__("Type") => array('fun'=>'systype_name'), 
	__("Trans #") => array('fun'=>'view_link'), 
	__("Counterparty") => array('fun' => 'person_link'),
	__("Supplier's Reference") => 'skip',
	__("Reference"), 
	__("Amount") => array('type'=>'amount'),
	__("Memo"),
	__("User"),
	__("View") => array('insert'=>true, 'fun'=>'gl_link'),
	array('insert'=>true, 'fun'=>'edit_link')
);

if (!check_value('AlsoClosed')) {
	$cols[__("#")] = 'skip';
}

if($_POST['filterType'] == ST_SUPPINVOICE) //add the payment column if shown supplier invoices only
{
	$cols[__("Supplier's Reference")] = array('fun'=>'invoice_supp_reference', 'align'=>'center');
}

$table =& new_db_pager('journal_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();

