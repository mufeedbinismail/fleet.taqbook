<?php

function check_supplier_deletable($supplier_id)
{
	if (key_in_foreign_table($supplier_id, 'supp_trans', 'supplier_id')) {
		return [
            'is_deletable' => false,
            'error' => __("Cannot delete this supplier because there are transactions that refer to this supplier."),
        ];
	} else if (key_in_foreign_table($supplier_id, 'purch_orders', 'supplier_id')) {
        return [
            'is_deletable' => false,
            'error' => __("Cannot delete the supplier record because purchase orders have been created against this supplier."),
        ];
	} else if (!is_new_supplier($supplier_id)) {
        return [
            'is_deletable' => false,
            'error' => __("Cannot delete the supplier because it has transactions associated with it."),
        ];
	}

	return [
        'is_deletable' => true,
        'error' => null,
    ];
}

function hydrate_post_with_common_supplier_data($supplier = null)
{
    if ($supplier && !is_array($supplier)) {
        $supplier = get_supplier($supplier);
    }

    if ($supplier) {
        $_POST['supp_name'] = $supplier["supp_name"];
		$_POST['supp_ref'] = $supplier["supp_ref"];
		$_POST['supp_address']  = $supplier["supp_address"];
        $_POST['address'] = $supplier['address'];
        $_POST['gst_no'] = $supplier['gst_no'];
        $_POST['supp_account_no'] = $supplier['supp_account_no'];
        $_POST['website'] = $supplier['website'];
        $_POST['bank_account'] = $supplier['bank_account'];
        $_POST['dimension_id']  = $supplier["dimension_id"];
		$_POST['dimension2_id']  = $supplier["dimension2_id"];
        $_POST['curr_code'] = $supplier['curr_code'];
        $_POST['payment_terms'] = $supplier['payment_terms'];
        $_POST['tax_group_id'] = $supplier['tax_group_id'];
        $_POST['tax_included'] = $supplier['tax_included'];
        $_POST['payable_account'] = $supplier['payable_account'];
        $_POST['purchase_account'] = $supplier['purchase_account'];
        $_POST['payment_discount_account'] = $supplier['payment_discount_account'];
        $_POST['credit_limit'] = price_format($supplier['credit_limit']);
        $_POST['notes'] = $supplier['notes'];
        $_POST['inactive'] = $supplier['inactive'];
    } else {
        $_POST['supp_name'] = '';
        $_POST['supp_ref'] = '';
        $_POST['supp_address'] = '';
        $_POST['address'] = '';
        $_POST['gst_no'] = '';
        $_POST['supp_account_no'] = '';
        $_POST['website'] = '';
        $_POST['bank_account'] = '';
        $_POST['dimension_id'] = 0;
        $_POST['dimension2_id'] = 0;
        $_POST['payment_terms'] = '';
        $_POST['tax_group_id'] = '';
        $_POST['tax_included'] = 0;
        $_POST['credit_limit'] = 0;
        $_POST['notes'] = '';
        $_POST['inactive'] = 0;

        $company_record = get_company_prefs();
        $_POST['curr_code'] = $company_record["curr_default"];
        $_POST['payable_account'] = $company_record["creditors_act"];
        $_POST['purchase_account'] = ''; // default/item's cogs account
        $_POST['payment_discount_account'] = $company_record['pyt_discount_act'];
    }
}

function read_supplier_data_from_post()
{
    return [
        'supp_name' => $_POST['supp_name'],
        'supp_ref' => $_POST['supp_ref'],
        'address' => $_POST['address'],
        'supp_address' => $_POST['supp_address'],
        'gst_no' => $_POST['gst_no'],
        'website' => $_POST['website'],
        'supp_account_no' => $_POST['supp_account_no'],
        'bank_account' => $_POST['bank_account'],
        'credit_limit' => input_num('credit_limit', 0),
        'dimension_id' => $_POST['dimension_id'],
        'dimension2_id' => $_POST['dimension2_id'],
        'curr_code' => $_POST['curr_code'],
        'payment_terms' => $_POST['payment_terms'],
        'payable_account' => $_POST['payable_account'],
        'purchase_account' => $_POST['purchase_account'],
        'payment_discount_account' => $_POST['payment_discount_account'],
        'notes' => $_POST['notes'],
        'tax_group_id' => $_POST['tax_group_id'],
        'tax_included' => check_value('tax_included'),
        'inactive' => check_value('inactive'),

        // CRM Person data
        'contact' => get_post('contact'),
        'phone' => get_post('phone'),
        'phone2' => get_post('phone2'),
        'fax' => get_post('fax'),
        'email' => get_post('email'),
        'rep_lang' => get_post('rep_lang'),
    ];
}

function validate_supplier_data($data)
{
    $error = fn ($message, $field) => [
        'is_valid' => false,
        'error' => $message,
        'field' => $field,
    ];

    $success = fn () => [
        'is_valid' => true,
        'error' => null,
        'field' => null,
    ];

    if (empty($data['supp_name'])) 
	{
		return $error(__("The supplier name must be entered."), 'supp_name');
	}

	if (empty($data['supp_ref'])) 
	{
		return $error(__("The supplier short name must be entered."), 'supp_ref');
	}

    // Validate supplier fields
    if (empty($data['curr_code'])) {
        return $error(__("The currency cannot be empty."), 'curr_code');
    }
    
    if (empty($data['tax_group_id'])) {
        return $error(__("The tax group must be selected."), 'tax_group_id');
    }
    
    if (empty($data['payment_terms'])) {
        return $error(__("The payment terms must be selected."), 'payment_terms');
    }
    
    $payable_account = $data['payable_account'] ?? $data['supplier_payable_account'] ?? '';
    if (empty($payable_account) || !key_in_foreign_table($payable_account, 'chart_master', 'account_code')) {
        return $error(__("The supplier payable account is not valid."), 'payable_account');
    }
    
    if (!empty($data['purchase_account']) && !key_in_foreign_table($data['purchase_account'], 'chart_master', 'account_code')) {
        return $error(__("The purchase/expense account is not valid."), 'purchase_account');
    }
    
    if (empty($data['payment_discount_account']) || !key_in_foreign_table($data['payment_discount_account'], 'chart_master', 'account_code')) {
        return $error(__("The payment discount account is not valid."), 'payment_discount_account');
    }
    
    return $success();
}

function common_supplier_settings_form($supplier_id, $options = [])
{

	text_row(__("GSTNo:"), 'gst_no', null, 42, 40);
	link_row(__("Website:"), 'website', null, 35, 55);
	if ($supplier_id && !is_new_supplier($supplier_id)) 
	{
		label_row(__("Supplier's Currency:"), $_POST['curr_code']);
		hidden('curr_code', $_POST['curr_code']);
	} 
	else 
	{
		currencies_list_row(__("Supplier's Currency:"), 'curr_code', null);
	}
	tax_groups_list_row(__("Tax Group:"), 'tax_group_id', null);
	text_row(__("Our Customer No:"), 'supp_account_no', null, 42, 40);

	table_section_title(__("Purchasing"));
	text_row(__("Bank Name/Account:"), 'bank_account', null, 42, 40);
	amount_row(__("Credit Limit:"), 'credit_limit', null);
	payment_terms_list_row(__("Payment Terms:"), 'payment_terms', null);
	//
	// tax_included option from supplier record is used directly in update_average_cost() function,
	// therefore we can't edit the option after any transaction was done for the supplier.
	//
	if (is_new_supplier($supplier_id))
		check_row(__("Prices contain tax included:"), 'tax_included');
	else {
		hidden('tax_included');
		label_row(__("Prices contain tax included:"), $_POST['tax_included'] ? __('Yes') : __('No'));
	}

	if (!$supplier_id) table_section(2);

	table_section_title(__("Accounts"));
	gl_all_accounts_list_row(__("Accounts Payable Account:"), 'payable_account', $_POST['payable_account']);
	gl_all_accounts_list_row(__("Purchase Account:"), 'purchase_account', $_POST['purchase_account'],
		false, false, __("Use Item Inventory/COGS Account"));
	gl_all_accounts_list_row(__("Purchase Discount Account:"), 'payment_discount_account', $_POST['payment_discount_account']);
    if ($options['show_provision_account'] ?? false) {
        gl_all_accounts_list_row(__("Provision Account:"), 'provision_account', null, true, false, __("-- select --"));
    }
	if (!$supplier_id) {
		table_section_title(__("Contact Data"));
		text_row(__("Contact Person:"), 'contact', null, 42, 40);
		text_row(__("Phone Number:"), 'phone', null, 32, 30);
		text_row(__("Secondary Phone Number:"), 'phone2', null, 32, 30);
		table_section_title(__("Contact Data"));
		text_row(__("Fax Number:"), 'fax', null, 32, 30);
		email_row(__("E-mail:"), 'email', null, 35, 55);
		languages_list_row(__("Document Language:"), 'rep_lang', null, __('System default'));
	}
	else
		table_section(2);
	$dim = get_company_pref('use_dimension');
	if ($dim >= 1)
	{
		table_section_title(__("Dimension"));
		dimensions_list_row(__("Dimension")." 1:", 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1)
			dimensions_list_row(__("Dimension")." 2:", 'dimension2_id', null, true, " ", false, 2);
	}
	if ($dim < 1)
		hidden('dimension_id', 0);
	if ($dim < 2)
		hidden('dimension2_id', 0);
	if (!$supplier_id)	
		table_section(2);

	table_section_title(__("Addresses"));
	textarea_row(__("Mailing Address:"), 'address', null, 35, 5);
	textarea_row(__("Physical Address:"), 'supp_address', null, 35, 5);

	table_section_title(__("General"));
	textarea_row(__("General Notes:"), 'notes', null, 35, 5);
}