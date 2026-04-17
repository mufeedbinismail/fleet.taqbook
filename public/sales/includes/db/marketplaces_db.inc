<?php

function create_marketplace($name, $code, $provision_account, $created_by, $supplier_id = null)
{
    $sql = "INSERT INTO ".TB_PREF."marketplaces (name, code, provision_account, supplier_id, created_by, created_at)
        VALUES ("
        .db_escape($name).", "
        .db_escape($code).", "
        .db_escape($provision_account).", "
        .db_escape($supplier_id).", "
        .db_escape($created_by).", "
        ."'".date('Y-m-d H:i:s')."')";
    
    db_query($sql, "The market place could not be created");

    return db_insert_id();
}

function update_marketplace($id, $name, $code, $provision_account, $updated_by, $inactive, $supplier_id = null)
{
    $sql = "UPDATE ".TB_PREF."marketplaces SET "
        ." name = ".db_escape($name).", "
        ." code = ".db_escape($code).", "
        ." provision_account = ".db_escape($provision_account).", "
        ." supplier_id = ".db_escape($supplier_id).", "
        ." updated_by = ".db_escape($updated_by).", "
        ." updated_at = '".date('Y-m-d H:i:s')."', "
        ." inactive = ".db_escape($inactive)." "
        ." WHERE id = ".db_escape($id);

    db_query($sql, "The market place could not be updated");
}

function delete_marketplace($id)
{
    $sql = "DELETE FROM ".TB_PREF."marketplaces WHERE id=".db_escape($id);
    db_query($sql, "The market place could not be deleted");
}

function get_marketplaces($inactive = false)
{
    $sql = "SELECT m.*, a.account_name as provision_account_name, s.supp_name as supplier_name FROM ".TB_PREF."marketplaces m"
        ." JOIN ".TB_PREF."chart_master a ON m.provision_account = a.account_code"
        ." LEFT JOIN ".TB_PREF."suppliers s ON m.supplier_id = s.supplier_id";
    if (!$inactive) {
        $sql .= " WHERE m.inactive=0";
    }
    $sql .= " ORDER BY m.name";

    return db_query($sql, "Could not get market places");
}

function get_marketplace($id)
{
    $sql = "SELECT
        m.*,
        a.account_name as provision_account_name,
        s.supp_name as supplier_name,
        s.tax_group_id,
        s.tax_included
    FROM ".TB_PREF."marketplaces m"
        ." JOIN ".TB_PREF."chart_master a ON m.provision_account = a.account_code"
        ." LEFT JOIN ".TB_PREF."suppliers s ON m.supplier_id = s.supplier_id"
        ." WHERE m.id=".db_escape($id);

    $result = db_query($sql, "Could not get market place");

    return db_fetch($result);
}

function get_marketplace_by_code($code)
{
    $sql = "SELECT * FROM ".TB_PREF."marketplaces WHERE code=".db_escape($code);

    $result = db_query($sql, "Could not get market place");

    return db_fetch($result);
}

function get_marketplace_by_name($name)
{
    $sql = "SELECT * FROM ".TB_PREF."marketplaces WHERE name=".db_escape($name);

    $result = db_query($sql, "Could not get market place");

    return db_fetch($result);
}

function get_marketplace_name($id)
{
    $data = get_marketplace($id);
    return $data ? $data['name'] : '';
}

function db_has_marketplaces()
{
    return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."marketplaces");
}

function get_marketplaces_search($marketplace)
{
	global $SysPrefs;

	if (isset($SysPrefs->max_rows_in_search))
		$limit = $SysPrefs->max_rows_in_search;
	else
		$limit = 10;

	$sql = "SELECT mp.id, mp.`name`, mp.code, mp.provision_account, cm.account_name as provision_account_name, s.supp_name as supplier_name
        FROM ".TB_PREF."marketplaces mp
        LEFT JOIN ".TB_PREF."chart_master cm ON cm.account_code = mp.provision_account
        LEFT JOIN ".TB_PREF."suppliers s ON mp.supplier_id = s.supplier_id
	    WHERE ( 
            mp.name LIKE " . db_escape("%" . $marketplace. "%") . " OR
            mp.code LIKE " . db_escape("%" . $marketplace. "%") . " OR
            cm.account_name LIKE " . db_escape("%" . $marketplace. "%") . " OR
            mp.provision_account LIKE " . db_escape("%" . $marketplace. "%") . " OR
            s.supp_name LIKE " . db_escape("%" . $marketplace. "%")
        . ")
	    ORDER BY mp.name
        LIMIT 0,".(int)($limit);

	return db_query($sql, "Failed in retrieving marketplace list.");
}