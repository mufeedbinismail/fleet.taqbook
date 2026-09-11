<?php

namespace App\Foundation\Shared\Setting;

use App\Foundation\Auth\Constant\DisplayPreference;
use App\Foundation\Framework\Support\Arr;
use App\Foundation\Shared\Enum\DateFormat;
use App\Foundation\Shared\Enum\DateSeparator;
use App\Foundation\Shared\Enum\DateSystem;
use App\Foundation\Shared\Enum\Skin;
use App\Foundation\Shared\Enum\TimeFormat;
use App\Foundation\Shared\Enum\WeekDay;
use App\Legacy\Enum\DecimalSeparator;
use App\Legacy\Enum\PageSize;
use App\Legacy\Enum\PrintDestination;
use App\Legacy\Enum\PrintOrientation;
use App\Legacy\Enum\ThousandSeparator;
use InvalidArgumentException;

class UserSetting extends Store
{
    /**
     * Default values keyed by db column.
     *
     * @return array<string, mixed>|mixed
     *
     * @throws InvalidArgumentException
     */
    public static function defaults(?string $key = null): mixed
    {
        $all = [
            DisplayPreference::LOCALE => app()->getLocale(),
            DisplayPreference::QUANTITY_DECIMALS => 0,
            DisplayPreference::PRICE_DECIMALS => 2,
            DisplayPreference::EXCHANGE_RATE_DECIMALS => 0,
            DisplayPreference::PERCENT_DECIMALS => 0,
            DisplayPreference::ENABLE_VIEW_GL_LINKS => 0,
            DisplayPreference::SHOW_ITEM_CODES_ALSO => 0,
            'time_format' => config('date.time_format_id'),
            DisplayPreference::DATE_FORMAT_IDX => config('date.format_id'),
            DisplayPreference::DATE_SEP_IDX => config('date.separator_id'),
            'week_start' => config('date.week_start_id'),
            DisplayPreference::THOUSAND_SEP_IDX => ThousandSeparator::COMMA->value,
            DisplayPreference::DECIMAL_SEP_IDX => DecimalSeparator::DOT->value,
            DisplayPreference::THEME => 'default',
            'skin' => Skin::System->value,
            DisplayPreference::PAGE_SIZE_IDX => PageSize::A4->value,
            DisplayPreference::SHOW_HINTS => 0,
            DisplayPreference::PRINT_PROFILE_NAME => null,
            DisplayPreference::SHOW_REPORT_AS_POPUP => 0,
            DisplayPreference::ROWS_PER_PAGE => 0,
            DisplayPreference::USE_ICONS_IN_LINKS => 0,
            DisplayPreference::USE_STICKY_DOC_DATE => 0,
            DisplayPreference::STARTUP_TAB => '',
            DisplayPreference::TRANSACTION_DAYS => -30,
            DisplayPreference::REPORT_SELECTION_RETENTION_DAYS => 0,
            DisplayPreference::USE_DATE_PICKER => 1,
            DisplayPreference::PRINT_DESTINATION_IDX => PrintDestination::PDF_PRINTER->value,
            DisplayPreference::PRINT_ORIENTATION_IDX => PrintOrientation::PORTRAIT->value,
        ];

        if ($key === null) {
            return $all;
        }

        if (! array_key_exists($key, $all)) {
            throw new InvalidArgumentException("Unknown user setting key: {$key}.");
        }

        return $all[$key];
    }

    public function __construct(array $user = [])
    {
        parent::__construct($this->prepare($user));
    }

    public function prepare(array $user): array
    {
        $items = [];
        $defaults = static::defaults();

        foreach ($defaults as $key => $default) {
            $items[$key] = Arr::kvGet($user, $key, $default);
        }

        if (! isset($user[DisplayPreference::USE_STICKY_DOC_DATE])) {
            $items['sticky_date'] = $defaults[DisplayPreference::USE_STICKY_DOC_DATE];
            $items[DisplayPreference::STARTUP_TAB] = $defaults[DisplayPreference::STARTUP_TAB];
        }

        if (! file_exists(public_path('themes/'.$items[DisplayPreference::THEME]))) {
            $items[DisplayPreference::THEME] = $defaults[DisplayPreference::THEME];
        }

        return $items;
    }

    public function setUser(?array $user): static
    {
        $this->items = $this->prepare($user ?: []);

        return $this;
    }

    public function locale(): string
    {
        return $this->items[DisplayPreference::LOCALE];
    }

    public function quantityDecimals(): int
    {
        return (int) $this->items[DisplayPreference::QUANTITY_DECIMALS];
    }

    public function priceDecimals(): int
    {
        return (int) $this->items[DisplayPreference::PRICE_DECIMALS];
    }

    public function exchangeRateDecimals(): int
    {
        return (int) $this->items[DisplayPreference::EXCHANGE_RATE_DECIMALS];
    }

    public function percentDecimals(): int
    {
        return (int) $this->items[DisplayPreference::PERCENT_DECIMALS];
    }

    public function enableViewGlLinks(): bool
    {
        return (bool) $this->items[DisplayPreference::ENABLE_VIEW_GL_LINKS];
    }

    public function showItemCodesAlso(): bool
    {
        return (bool) $this->items[DisplayPreference::SHOW_ITEM_CODES_ALSO];
    }

    public function dateFormatIdx(): DateFormat
    {
        return DateFormat::from((int) $this->items[DisplayPreference::DATE_FORMAT_IDX]);
    }

    public function dateSepIdx(): DateSeparator
    {
        return DateSeparator::from((int) $this->items[DisplayPreference::DATE_SEP_IDX]);
    }

    public function thousandSepIdx(): ThousandSeparator
    {
        return ThousandSeparator::from((int) $this->items[DisplayPreference::THOUSAND_SEP_IDX]);
    }

    public function decimalSepIdx(): DecimalSeparator
    {
        return DecimalSeparator::from((int) $this->items[DisplayPreference::DECIMAL_SEP_IDX]);
    }

    public function theme(): string
    {
        return $this->items[DisplayPreference::THEME];
    }

    public function skin(): Skin
    {
        return Skin::from($this->items['skin']);
    }

    public function pageSizeIdx(): PageSize
    {
        return PageSize::from($this->items[DisplayPreference::PAGE_SIZE_IDX]);
    }

    public function showHints(): bool
    {
        return (bool) $this->items[DisplayPreference::SHOW_HINTS];
    }

    public function printProfileName(): ?string
    {
        return $this->items[DisplayPreference::PRINT_PROFILE_NAME];
    }

    public function showReportAsPopup(): bool
    {
        return (bool) $this->items[DisplayPreference::SHOW_REPORT_AS_POPUP];
    }

    public function rowsPerPage(): int
    {
        return (int) $this->items[DisplayPreference::ROWS_PER_PAGE];
    }

    public function useIconsInLinks(): bool
    {
        return (bool) $this->items[DisplayPreference::USE_ICONS_IN_LINKS];
    }

    public function useStickyDocDate(): bool
    {
        return (bool) $this->items[DisplayPreference::USE_STICKY_DOC_DATE];
    }

    /**
     * Nothing here checks the tab still names somewhere reachable: a preference outlives the
     * permissions of whoever set it.
     */
    public function startupTab(): string
    {
        return $this->items[DisplayPreference::STARTUP_TAB];
    }

    public function transactionDays(): int
    {
        return (int) $this->items[DisplayPreference::TRANSACTION_DAYS];
    }

    public function reportSelectionRetentionDays(): int
    {
        return (int) $this->items[DisplayPreference::REPORT_SELECTION_RETENTION_DAYS];
    }

    public function useDatePicker(): bool
    {
        return (bool) $this->items[DisplayPreference::USE_DATE_PICKER];
    }

    public function printDestinationIdx(): PrintDestination
    {
        return PrintDestination::from((int) $this->items[DisplayPreference::PRINT_DESTINATION_IDX]);
    }

    public function printOrientationIdx(): PrintOrientation
    {
        return PrintOrientation::from((int) $this->items[DisplayPreference::PRINT_ORIENTATION_IDX]);
    }

    public function dateFormat(): string
    {
        return $this->dateFormatIdx()->format($this->dateSepIdx());
    }

    public function timeFormatIdx(): TimeFormat
    {
        return TimeFormat::from((int) $this->items['time_format']);
    }

    public function timeFormat(): string
    {
        return $this->timeFormatIdx()->format();
    }

    public function timeFormatWithSeconds(): string
    {
        return $this->timeFormatIdx()->formatWithSeconds();
    }

    public function dateTimeFormat(): string
    {
        return $this->dateFormat().' '.$this->timeFormat();
    }

    /**
     * The day standing against this account, which is the installation's until the account says
     * otherwise. Null only where the installation named none either, and it is offered to a form
     * rather than resolved so that the question can still be left open there.
     */
    public function chosenWeekStart(): ?WeekDay
    {
        return WeekDay::fromChoice($this->items['week_start']);
    }

    public function weekStart(): WeekDay
    {
        return $this->chosenWeekStart() ?? $this->inferredWeekStart();
    }

    /**
     * Deliberately a poor rule: it reads a calendar convention off a display preference, and the
     * two vary apart. It is kept because a week beginning on a different day either side of the
     * port boundary is misread at a glance rather than noticed.
     */
    private function inferredWeekStart(): WeekDay
    {
        if ($this->calendarSystem()->startsWeekOnSaturday()) {
            return WeekDay::Saturday;
        }

        return $this->dateFormatIdx()->writesMonthFirst() ? WeekDay::Sunday : WeekDay::Monday;
    }

    public function calendarSystem(): DateSystem
    {
        return DateSystem::from((int) config('date.calendar_system_id'));
    }
}
