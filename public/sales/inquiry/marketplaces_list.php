<?php

$GLOBALS['page_security'] = "SA_MP_SALESORDER";
require_once __DIR__ . "/../../includes/session.inc";
require_once __DIR__ . "/../../includes/ui.inc";
require_once __DIR__ . "/../../sales/includes/db/marketplaces_db.inc";

$js = get_js_select_combo_item();

page(__($GLOBALS['help_context'] = "Marketplaces"), true, false, "", $js);

if(get_post("search")) {
  $Ajax->activate("marketplace_tbl");
}

start_form(false, false, url()->current() . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

text_cells(__("Marketplace"), "marketplace");
submit_cells("search", __("Search"), "", __("Search marketplaces"), "default");

end_row();

end_table();

end_form();

div_start("marketplace_tbl");

start_table(TABLESTYLE);

$th = array("", __("Marketplace"), __("Code"), __("Provision Account"));

table_header($th);

$k = 0;
$name = $_GET["client_id"];
$result = get_marketplaces_search(get_post("marketplace"));
while ($myrow = db_fetch_assoc($result)) {
	alt_table_row_color($k);
	$value = $myrow['id'];
    ahref_cell(__("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, "'.$name.'", "'.$value.'")');
  	label_cell($myrow["name"]);
  	label_cell($myrow["code"]);
  	label_cell($myrow["provision_account"] . " - " . $myrow["provision_account_name"]);
	end_row();
}

end_table(1);

div_end();

end_page(true);
