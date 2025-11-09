<?php

return [
    // Language and interface globals
    'installed_languages'  => [['code' => 'C', 'name' => 'English', 'encoding' => 'iso-8859-1']],
    'dflt_lang'            => 'C',
    'lang'                 => null,
    
    // Database globals
    'db'                   => [],
    'db_connections'       => [],
    'db_version'           => null,
    'db_last_inserted_id'  => 0,
    
    // Company and preferences
    'def_coy'              => 0,
    'tb_pref_counter'      => 0,
    'SysPrefs'             => null,
    
    // Page and navigation globals
    'page_nested'          => -1,
    'form_nested'          => -1,
    'before_box'           => '',
    'messages'             => [],
    'Refs'                 => [],
    'Mode'                 => null,
    'Mode2'                => null,
    'selected_id'          => null,
    'selected_id2'         => null,
    
    // Security globals
    'security_areas'       => [],
    'security_sections'    => [],
    
    // Form and UI globals  
    'hidden_fields'        => [],
    'ajax_divs'            => [],
    'Editors'              => [],
    'popup_editors'        => [],
    'Pagehelp'             => [],
    'Validate'             => [],
    'help_context'         => null,
    
    // JavaScript and CSS
    'js_lib'               => [],
    'js_path'              => PATH_TO_ROOT.'/js/',
    'js_static'            => [],
    'js_userlib'           => [],
    'css_files'            => [],
    
    // System configuration
    'no_exchange_variations' => [],
    'payment_services'     => [],
    'stock_types'          => [],
    'bank_account_types'   => [],
    'payment_person_types' => [],
    'quick_actions'        => [],
    'quick_entry_types'    => [],
    'class_types'          => [],
    'pterm_types'          => [],
    'tax_algorithms'       => [],
    'supported_collations' => [],
    'bank_transfer_types'  => [],
    'tag_types'            => [],
    'bank_owner'           => null,
    'refline_options'      => [],
    'refline_placeholders' => [],
    'compare_types'        => [],
    'depreciation_methods' => [],
    'document_child_types' => [],
    'wo_cost_types'        => [],
    
    // System state
    'sql_queries'          => '',
    'login_faillog'        => [],
    'cur_error_level'      => null,
    'SessionManager'       => null,
    'date_system'          => null,
    'test_level'           => null,
    'transaction_level'    => 0,
    
    // Version metadata
    'repo_auth'            => [],
    'src_version'          => null,
    
    // Business logic
    'bom_list'             => [],
    'qoh_stock'            => null,
    'Ajax'                 => null,
    'systypes_array'       => [],
    'GetText'              => null,
    'wo_types'             => [],
    'type_shortcuts'       => [],
    'page_security'        => null,
    'version'              => null,
    'tmonths'              => [],
];
