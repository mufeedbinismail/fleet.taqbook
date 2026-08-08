<?php

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Shared\ValueObject\TypedId;

$GLOBALS['page_security'] = Permission::VIEW_MARKETPLACE_SUPPLIER_TRANSACTION;

require_once __DIR__ . "/../includes/db_pager.inc";
require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_ui.inc";
require_once __DIR__ . "/includes/marketplace_supplier_trans_db.inc";

global $Ajax;

$js = user_use_date_picker() ? get_js_date_picker() : "";
page(__("Marketplace Supplier Fee Inquiry"), false, false, "", $js);

//------------------------------------------------------------------------------------------------

function systype_name($dummy, $type)
{
	global $systypes_array;
	return $systypes_array[$type];
}

function trans_view_link($row, $value)
{
    $typedId = TypedId::make($row['type'], $row['trans_no']);
    if (is_int($value) && !empty($value)) {
        return get_trans_view_str($row['type'], $row['trans_no']);
    } else {
        return pager_link(__("View"), "/marketplace/view_marketplace_supplier_trans.php?trans_id=" . $typedId->toString(), ICON_VIEW);
    }
}

function gl_view_link($row)
{
    return get_gl_view_str($row['type'], $row['trans_no']);
}

//------------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();

marketplace_list_cells(__("Marketplace:"), 'marketplace_id', null, true, false);
date_cells(__("From:"), 'TransAfterDate', '', null, -user_transaction_days());
date_cells(__("To:"), 'TransToDate');
submit_cells('RefreshInquiry', __("Search"), '', __('Refresh Inquiry'), 'default');

end_row();
end_table();

if (get_post('RefreshInquiry') || list_updated('marketplace_id')) {
    $Ajax->activate('_page_body');
}

//------------------------------------------------------------------------------------------------

$sql = get_sql_for_marketplace_supplier_trans_list(
    get_post('marketplace_id'),
    get_post('TransAfterDate'),
    get_post('TransToDate')
);

$cols = array(
    __("Type")            => array('fun'  => 'systype_name'),
    __("#")               => array('fun'  => 'trans_view_link',  'name' => 'trans_no', 'align' => 'right', 'ord' => ''),
    __("Reference")       => array('name' => 'reference',        'type' => 'text',     'ord' => ''),
    __("Marketplace's #") => array('name' => 'marketplace_ref',  'type' => 'text'),
    __("Marketplace")     => array('name' => 'marketplace_name', 'type' => 'text'),
    __("Date")            => array('name' => 'tran_date',        'type' => 'date',     'ord' => 'desc'),
    __("Total")           => array('name' => 'total',            'type' => 'amount',   'align' => 'right'),
    array('insert' => true, 'fun' => 'trans_view_link'),
    array('insert' => true, 'fun' => 'gl_view_link'),
);

$table =& new_db_pager('mktpl_st_list', $sql, $cols);
$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
