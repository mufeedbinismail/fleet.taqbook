<?php

use App\Legacy\Enum\ChartSkin;
use App\Legacy\Enum\DateFormat;
use App\Legacy\Enum\DateSeparator;
use App\Legacy\Enum\DateSystem;
use App\Legacy\Enum\ThousandSeparator;
use App\Legacy\Enum\DecimalSeparator;
use App\Legacy\Enum\PageSize;
use App\Legacy\Enum\ExchangeRateProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Error Log File
    |--------------------------------------------------------------------------
    |
    | Path for PHP error/warning log. When empty, logging is switched off.
    | Use 'syslog' for system logger. Must be writable by the web server.
    |
    */
    'error_logfile' => storage_path('logs/legacy_errors.log'),

    /*
    |--------------------------------------------------------------------------
    | Debug (SQL on DB errors)
    |--------------------------------------------------------------------------
    |
    | When true, show SQL on database errors. When false, do not show debugging info.
    | PHP.ini also affects error display.
    |
    */
    'debug' => env('LEGACY_SHOW_SQL_ON_DB_ERROR', false),

    /*
    |--------------------------------------------------------------------------
    | Allow only HTTPS mode
    |--------------------------------------------------------------------------
    |
    | When true, accessing through HTTP will result in a blocked message
    | 
    | "HTTP access is not allowed on this site. This is unsecure. If you
    | really want to access this unsecure site then set LEGACY_ALLOW_HTTPS_ONLY"
    |
    */
    'secure_only' => env('LEGACY_ALLOW_HTTPS_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Show SQL in Footer
    |--------------------------------------------------------------------------
    |
    | When true, show all SQL queries in the page footer for debugging.
    |
    */
    'show_sql' => env('LEGACY_SHOW_SQL_LOG_IN_FOOTER', false),

    /*
    |--------------------------------------------------------------------------
    | Go Debug Level
    |--------------------------------------------------------------------------
    |
    | 0 = off. 1 = basic debugging. 2 = include backtrace after failure.
    |
    */
    'go_debug' => env('LEGACY_DEBUG_LEVEL', 0),

    /*
    |--------------------------------------------------------------------------
    | PDF Debug
    |--------------------------------------------------------------------------
    |
    | When true and go_debug is set, display PDF source instead of reports.
    |
    */
    'pdf_debug' => env('LEGACY_DEBUG_PDF_ALSO', false),

    /*
    |--------------------------------------------------------------------------
    | SQL Trail
    |--------------------------------------------------------------------------
    |
    | When true, save all SQL in sql_trail table. Produces large data; turn off
    | and flush table after debugging.
    |
    */
    'sql_trail' => env('LEGACY_ENABLE_SQL_TRAIL', false),

    /*
    |--------------------------------------------------------------------------
    | Select Trail
    |--------------------------------------------------------------------------
    |
    | When true and sql_trail is on, also log SELECT queries.
    |
    */
    'select_trail' => env('LEGACY_TRAIL_SELECT_QUERIES_ALSO', false),

    /*
    |--------------------------------------------------------------------------
    | Application Title
    |--------------------------------------------------------------------------
    |
    | Main application title shown in the UI and documents.
    | Deprecated: hardcoded to app.name
    |
    */
    'app_title' => null,

    /*
    |--------------------------------------------------------------------------
    | Build Version
    |--------------------------------------------------------------------------
    |
    | Version string derived from CHANGELOG.txt filemtime, or current date.
    |
    */
    'build_version' => file_exists(base_path('public/CHANGELOG.txt'))
        ? date('d.m.Y', filemtime(base_path('public/CHANGELOG.txt')))
        : date('d.m.Y'),

    /*
    |--------------------------------------------------------------------------
    | Powered By Label
    |--------------------------------------------------------------------------
    |
    | "Powered by" text in footer/reports.
    | Deprecated: hardcoded to app.name
    |
    */
    'power_by' => null,

    /*
    |--------------------------------------------------------------------------
    | Powered By URL
    |--------------------------------------------------------------------------
    |
    | URL for "Powered by" link.
    |
    */
    'power_url' => '#',

    /*
    |--------------------------------------------------------------------------
    | Company Data Path
    |--------------------------------------------------------------------------
    |
    | Per-company data/cache directory. Relative paths start with '.'.
    |
    */
    'comp_path' => base_path('public/company'),

    /*
    |--------------------------------------------------------------------------
    | No Check Edit Conflicts
    |--------------------------------------------------------------------------
    |
    | When true, skip edit conflict check. May be needed on some Windows servers.
    |
    */
    'no_check_edit_conflicts' => false,

    /*
    |--------------------------------------------------------------------------
    | Use Icon for Edit Key
    |--------------------------------------------------------------------------
    |
    | When true, show edit icon next to supplier/customer combobox.
    |
    */
    'use_icon_for_editkey' => false,

    /*
    |--------------------------------------------------------------------------
    | Auto Create Branch
    |--------------------------------------------------------------------------
    |
    | When true, automatically create a default branch with contact for new customer.
    | When false, do not create.
    |
    */
    'auto_create_branch' => true,

    /*
    |--------------------------------------------------------------------------
    | Use Popup Windows
    |--------------------------------------------------------------------------
    |
    | When true, open view screens in popup windows.
    |
    */
    'use_popup_windows' => true,

    /*
    |--------------------------------------------------------------------------
    | Use Audit Trail (GL)
    |--------------------------------------------------------------------------
    |
    | Deprecated. When true, stamped user in memo. Superseded by built-in Audit Trail.
    |
    */
    'use_audit_trail' => false,

    /*
    |--------------------------------------------------------------------------
    | Use Old Style Convert
    |--------------------------------------------------------------------------
    |
    | Old P&L/BS treatment of income and expense.
    | When false, standard.
    | When true, old style.
    |
    */
    'use_oldstyle_convert' => false,

    /*
    |--------------------------------------------------------------------------
    | Show Users Online
    |--------------------------------------------------------------------------
    |
    | When true, show users online in footer.
    |
    */
    'show_users_online' => true,

    /*
    |--------------------------------------------------------------------------
    | Old Style Help
    |--------------------------------------------------------------------------
    |
    | Deprecated. When true, use translated help page titles.
    |
    */
    'old_style_help' => false,

    /*
    |--------------------------------------------------------------------------
    | Help Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL for context help. Null to disable help.
    |
    */
    'help_base_url' => '#?n=Help',

    /*
    |--------------------------------------------------------------------------
    | Date System
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\DateSystem.
    |
    */
    'date_system' => DateSystem::Traditional->value,

    /*
    |--------------------------------------------------------------------------
    | Allow GL Reopen
    |--------------------------------------------------------------------------
    |
    | When true, allow reopening closed GL periods.
    |
    */
    'allow_gl_reopen' => true,

    /*
    |--------------------------------------------------------------------------
    | Date Formats
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\DateFormat.
    |
    */
    'dateformats' => get_labels_from_enum(DateFormat::class),

    /*
    |--------------------------------------------------------------------------
    | Date Separators
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\DateSeparator.
    |
    */
    'dateseps' => get_labels_from_enum(DateSeparator::class),

    /*
    |--------------------------------------------------------------------------
    | Thousands Separators
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\ThousandSeparator.
    |
    */
    'thoseps' => get_labels_from_enum(ThousandSeparator::class),

    /*
    |--------------------------------------------------------------------------
    | Decimal Separators
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\DecimalSeparator.
    |
    */
    'decseps' => get_labels_from_enum(DecimalSeparator::class),

    /*
    |--------------------------------------------------------------------------
    | Default Date Format Index
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\DateFormat.
    |
    */
    'dflt_date_fmt' => DateFormat::DDMMYYYY->value,

    /*
    |--------------------------------------------------------------------------
    | Default Date Separator Index
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\DateSeparator.
    |
    */
    'dflt_date_sep' => DateSeparator::SLASH->value,

    /*
    |--------------------------------------------------------------------------
    | PDF Page Sizes
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\PageSize.
    |
    */
    'pagesizes' => get_labels_from_enum(PageSize::class),

    /*
    |--------------------------------------------------------------------------
    | Check Qty Charged vs Delivered
    |--------------------------------------------------------------------------
    |
    | When true, validate purchase invoice qty vs received before overcharge error.
    |
    */
    'check_qty_charged_vs_del_qty' => true,

    /*
    |--------------------------------------------------------------------------
    | Check Price Charged vs Order Price
    |--------------------------------------------------------------------------
    |
    | When true, validate purchase invoice price vs PO before overcharge error.
    |
    */
    'check_price_charged_vs_order_price' => true,

    /*
    |--------------------------------------------------------------------------
    | Allocation Settled Allowance
    |--------------------------------------------------------------------------
    |
    | Tolerance for allocation "settled" (e.g. 0.005).
    |
    */
    'config_allocation_settled_allowance' => 0.005,

    /*
    |--------------------------------------------------------------------------
    | Use Costed Values
    |--------------------------------------------------------------------------
    |
    | When true, show average costed values in Inventory Valuation Report.
    |
    */
    'use_costed_values' => true,

    /*
    |--------------------------------------------------------------------------
    | Show Menu Category Icons
    |--------------------------------------------------------------------------
    |
    | When true, show icons for menu categories in core themes.
    |
    */
    'show_menu_category_icons' => true,

    /*
    |--------------------------------------------------------------------------
    | Allow Demo Mode
    |--------------------------------------------------------------------------
    |
    | When true, display demo login and password on login screen.
    |
    */
    'allow_demo_mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Login Delay (seconds)
    |--------------------------------------------------------------------------
    |
    | Seconds to wait between login attempts after login_max_attempts failures.
    | 0 = disable (not recommended).
    |
    */
    'login_delay' => 300,

    /*
    |--------------------------------------------------------------------------
    | Login Max Attempts
    |--------------------------------------------------------------------------
    |
    | Failed attempts before login_delay is applied.
    |
    */
    'login_max_attempts' => 10,

    /*
    |--------------------------------------------------------------------------
    | Picture Width (px)
    |--------------------------------------------------------------------------
    |
    | Display width for item pictures.
    |
    */
    'pic_width' => 80,

    /*
    |--------------------------------------------------------------------------
    | Picture Height (px)
    |--------------------------------------------------------------------------
    |
    | Display height for item pictures.
    |
    */
    'pic_height' => 50,

    /*
    |--------------------------------------------------------------------------
    | Max Image Size (KB)
    |--------------------------------------------------------------------------
    |
    | Maximum upload size for images in KB.
    |
    */
    'max_image_size' => 500,

    /*
    |--------------------------------------------------------------------------
    | Graph Skin
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\ChartSkin.
    |
    */
    'graph_skin' => ChartSkin::Office->value,

    /*
    |--------------------------------------------------------------------------
    | UTF-8 Font File (LTR)
    |--------------------------------------------------------------------------
    |
    | Font file for business graphics (LTR). Place in reporting/fonts/.
    |
    */
    'UTF8_fontfile' => 'FreeSans.ttf',

    /*
    |--------------------------------------------------------------------------
    | UTF-8 Font File (RTL)
    |--------------------------------------------------------------------------
    |
    | Font file for business graphics (RTL).
    |
    */
    'utf8_fontfile_rtl' => 'zarnormal.ttf',

    /*
    |--------------------------------------------------------------------------
    | Text Company Selection
    |--------------------------------------------------------------------------
    |
    | When false, dropdown for company. When true, blank edit for nickname (privacy).
    |
    */
    'text_company_selection' => false,

    /*
    |--------------------------------------------------------------------------
    | Hide Inaccessible Menu Items
    |--------------------------------------------------------------------------
    |
    | When true, hide menu items the user has no access to.
    | When false, show menu items in a disabled state even if the user has no access to.
    |
    */
    'hide_inaccessible_menu_items' => true,

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Providers
    |--------------------------------------------------------------------------
    |
    | Possible values are defined in \App\Legacy\Enum\ExchangeRateProvider.
    |
    */
    'xr_providers' => get_labels_from_enum(ExchangeRateProvider::class),

    /*
    |--------------------------------------------------------------------------
    | Default Exchange Rate Provider Index
    |--------------------------------------------------------------------------
    |
    | Index into xr_providers for default provider.
    |
    */
    'dflt_xr_provider' => ExchangeRateProvider::GOOGLE->value,

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Provider Authoritative
    |--------------------------------------------------------------------------
    |
    | When true, store remote rate automatically. Else store on first tx of day.
    |
    */
    'xr_provider_authoritative' => false,

    /*
    |--------------------------------------------------------------------------
    | Sort Sales Items
    |--------------------------------------------------------------------------
    |
    | When true, sort sales document lines by item code during entry.
    |
    */
    'sort_sales_items' => false,

    /*
    |--------------------------------------------------------------------------
    | Clear Trial Balance Opening
    |--------------------------------------------------------------------------
    |
    | When true, clear past years in trial balance opening balance display.
    |
    */
    'clear_trial_balance_opening' => false,

    /*
    |--------------------------------------------------------------------------
    | Use Popup Search
    |--------------------------------------------------------------------------
    |
    | When true, use popup for search/lookup.
    |
    */
    'use_popup_search' => true,

    /*
    |--------------------------------------------------------------------------
    | Max Rows in Search
    |--------------------------------------------------------------------------
    |
    | Maximum rows returned in search results.
    |
    */
    'max_rows_in_search' => 15,
];
