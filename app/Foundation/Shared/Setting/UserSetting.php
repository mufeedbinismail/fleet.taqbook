<?php

namespace App\Foundation\Shared\Setting;

use App\Foundation\Framework\Support\Arr;
use App\Legacy\Enum\DateFormat;
use App\Legacy\Enum\DateSeparator;
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
     * @param  string|null  $key  Db column name. If null, returns all defaults.
     * @return array<string, mixed>|mixed
     *
     * @throws InvalidArgumentException When key is provided but does not exist.
     */
    public static function defaults(?string $key = null): mixed
    {
        $all = [
            'language' => app()->getLocale(),
            'qty_dec' => 0,
            'prices_dec' => 2,
            'rates_dec' => 0,
            'percent_dec' => 0,
            'show_gl' => 0,
            'show_codes' => 0,
            'date_format' => config('legacy.dflt_date_fmt'),
            'date_sep' => config('legacy.dflt_date_sep'),
            'tho_sep' => ThousandSeparator::COMMA->value,
            'dec_sep' => DecimalSeparator::DOT->value,
            'theme' => 'default',
            'page_size' => PageSize::A4->value,
            'show_hints' => 0,
            'print_profile' => null,
            'rep_popup' => 0,
            'query_size' => 0,
            'graphic_links' => 0,
            'sticky_doc_date' => 0,
            'startup_tab' => '',
            'transaction_days' => -30,
            'save_report_selections' => 0,
            'use_date_picker' => 1,
            'def_print_destination' => PrintDestination::PDF_PRINTER->value,
            'def_print_orientation' => PrintOrientation::PORTRAIT->value,
        ];

        if ($key === null) {
            return $all;
        }

        if (!array_key_exists($key, $all)) {
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

        if (!isset($user['sticky_doc_date'])) {
            $items['sticky_date'] = $defaults['sticky_doc_date'];
            $items['startup_tab'] = $defaults['startup_tab'];
        }

        if (!file_exists(public_path('themes/' . $items['theme']))) {
            $items['theme'] = $defaults['theme'];
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
        return $this->items['language'];
    }

    public function quantityDecimals(): int
    {
        return (int) $this->items['qty_dec'];
    }

    public function priceDecimals(): int
    {
        return (int) $this->items['prices_dec'];
    }

    public function exchangeRateDecimals(): int
    {
        return (int) $this->items['rates_dec'];
    }

    public function percentDecimals(): int
    {
        return (int) $this->items['percent_dec'];
    }

    public function enableViewGlLinks(): bool
    {
        return (bool) $this->items['show_gl'];
    }

    public function showItemCodesAlso(): bool
    {
        return (bool) $this->items['show_codes'];
    }

    public function dateFormatIdx(): DateFormat
    {
        return DateFormat::from((int) $this->items['date_format']);
    }

    public function dateSepIdx(): DateSeparator
    {
        return DateSeparator::from((int) $this->items['date_sep']);
    }

    public function thousandSepIdx(): ThousandSeparator
    {
        return ThousandSeparator::from((int) $this->items['tho_sep']);
    }

    public function decimalSepIdx(): DecimalSeparator
    {
        return DecimalSeparator::from((int) $this->items['dec_sep']);
    }

    public function theme(): string
    {
        return $this->items['theme'];
    }

    public function pageSizeIdx(): PageSize
    {
        return PageSize::from($this->items['page_size']);
    }

    public function showHints(): bool
    {
        return (bool) $this->items['show_hints'];
    }

    public function printProfileName(): ?string
    {
        return $this->items['print_profile'];
    }

    public function showReportAsPopup(): bool
    {
        return (bool) $this->items['rep_popup'];
    }

    public function rowsPerPage(): int
    {
        return (int) $this->items['query_size'];
    }

    public function useIconsInLinks(): bool
    {
        return (bool) $this->items['graphic_links'];
    }

    public function useStickyDocDate(): bool
    {
        return (bool) $this->items['sticky_doc_date'];
    }

    /**
     * Where this user asked to land, or an empty string when they never said.
     *
     * Nothing here checks that the answer still names somewhere reachable — a preference outlives
     * the permissions of whoever set it, so that is the caller's question at the moment it lands.
     */
    public function startupTab(): string
    {
        return $this->items['startup_tab'];
    }

    public function transactionDays(): int
    {
        return (int) $this->items['transaction_days'];
    }

    public function reportSelectionRetentionDays(): int
    {
        return (int) $this->items['save_report_selections'];
    }

    public function useDatePicker(): bool
    {
        return (bool) $this->items['use_date_picker'];
    }

    public function printDestinationIdx(): PrintDestination
    {
        return PrintDestination::from((int) $this->items['def_print_destination']);
    }

    public function printOrientationIdx(): PrintOrientation
    {
        return PrintOrientation::from((int) $this->items['def_print_orientation']);
    }

    public function dateFormat(): string
    {
        return $this->dateFormatIdx()->format($this->dateSepIdx());
    }
}
