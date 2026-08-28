<?php

namespace App\Foundation\Component\Date\Source;

/**
 * The date settings a page states once for every field on it, rather than per field.
 *
 * Only what can differ. Month and day names are absent deliberately: PHP's date formatting ignores
 * the locale — `F` answers "March" whatever it is set to — and `createFromFormat` reads back only
 * those spellings, so they are not configuration at all.
 */
final class DateSource
{
    public const NAMESPACE = 'date';

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        // Worked out afresh each time: held, this would answer for whoever was logged in when it
        // was first asked.
        return [
            'format' => user_settings()->dateFormat(),
            'dateTimeFormat' => user_settings()->dateTimeFormat(),
            'timeFormat' => user_settings()->timeFormat(),
            'firstDay' => user_settings()->weekStart()->value,
            'locale' => [
                'today' => __('foundation.date.today'),
                'clear' => __('foundation.date.clear'),
            ],
        ];
    }
}
