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
$GLOBALS['page_security'] = 'SA_SALESTRANSVIEW';
require_once __DIR__ . "/../../includes/session.inc";

require_once __DIR__ . "/../../includes/date_functions.inc";
require_once __DIR__ . "/../../includes/ui.inc";
require_once __DIR__ . "/../../sales/includes/sales_db.inc";

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);

page(__($GLOBALS['help_context'] = "View Customer Refund"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}

// A marketplace customer refund is the mirror of a settlement: AR is re-opened against
// the marketplace AP, with no bank leg. get_customer_trans is called with
// ST_MKTCUSTREFUND (not ST_CUSTPAYMENT) so it never joins bank_trans/bank_accounts,
// which a refund does not have. The credit notes that fund it are shown as allocations
// to this refund.
$refund = get_customer_trans($trans_id, ST_MKTCUSTREFUND);

if (!empty($SysPrefs->prefs['company_logo_on_views']))
	company_logo_on_view();

display_heading(sprintf(__("Customer Refund #%d"), $trans_id));

echo "<br>";
start_table(TABLESTYLE, "width='80%'");
start_row();
label_cells(__("From Customer"), $refund['DebtorName'], "class='tableheader2'");
label_cells(__("Reference"), $refund['reference'], "class='tableheader2'");
label_cells(__("Date"), sql2date($refund['tran_date']), "class='tableheader2'");
end_row();
start_row();
label_cells(__("Customer Currency"), $refund['curr_code'], "class='tableheader2'");
label_cells(__("Amount"), price_format($refund['Total']), "class='tableheader2'");
label_cells(__("Marketplace"), get_marketplace_name($refund['marketplace_id']), "class='tableheader2'");
end_row();
comments_display_row(ST_MKTCUSTREFUND, $trans_id);

end_table(1);

$voided = is_voided_display(ST_MKTCUSTREFUND, $trans_id, __("This customer refund has been voided."));

if (!$voided)
{
	// Credit notes are allocated to the refund (refund is the allocatee / `to` side).
	display_allocations_to(PT_CUSTOMER, $refund['debtor_no'], ST_MKTCUSTREFUND, $trans_id, $refund['Total']);
}

end_page(true, false, false, ST_MKTCUSTREFUND, $trans_id);
