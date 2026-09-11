<?php

namespace App\Foundation\Auth\Constant;

use ReflectionClass;

/**
 * The columns of a user row that belong to the person rather than to whoever administers them,
 * each named as the setting that reads it.
 */
final class DisplayPreference
{
    public const THEME = 'theme';

    public const PAGE_SIZE_IDX = 'page_size';

    public const LOCALE = 'language';

    public const STARTUP_TAB = 'startup_tab';

    public const DATE_FORMAT_IDX = 'date_format';

    public const DATE_SEP_IDX = 'date_sep';

    public const THOUSAND_SEP_IDX = 'tho_sep';

    public const DECIMAL_SEP_IDX = 'dec_sep';

    public const PRICE_DECIMALS = 'prices_dec';

    public const QUANTITY_DECIMALS = 'qty_dec';

    public const EXCHANGE_RATE_DECIMALS = 'rates_dec';

    public const PERCENT_DECIMALS = 'percent_dec';

    public const ENABLE_VIEW_GL_LINKS = 'show_gl';

    public const SHOW_ITEM_CODES_ALSO = 'show_codes';

    public const SHOW_HINTS = 'show_hints';

    public const USE_ICONS_IN_LINKS = 'graphic_links';

    public const ROWS_PER_PAGE = 'query_size';

    public const TRANSACTION_DAYS = 'transaction_days';

    public const REPORT_SELECTION_RETENTION_DAYS = 'save_report_selections';

    public const USE_STICKY_DOC_DATE = 'sticky_doc_date';

    public const USE_DATE_PICKER = 'use_date_picker';

    public const SHOW_REPORT_AS_POPUP = 'rep_popup';

    public const PRINT_PROFILE_NAME = 'print_profile';

    public const PRINT_DESTINATION_IDX = 'def_print_destination';

    public const PRINT_ORIENTATION_IDX = 'def_print_orientation';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values((new ReflectionClass(self::class))->getConstants());
    }
}
