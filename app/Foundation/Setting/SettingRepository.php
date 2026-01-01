<?php

namespace App\Foundation\Setting;

use App\Finance\Tax\Enum\TaxAlgorithm;
use App\Foundation\Model\Setting;
use App\Legacy\Enum\AccountCodeFormat;
use App\Legacy\Enum\DepreciationPeriodType;

class SettingRepository extends Repository
{
    /**
     * Load the configuration values from db
     *
     * @return void
     */
    protected function items(): array
    {
        return Setting::pluck('value', 'name')->toArray();
    }

    /**
     * Create a new settings repository instance.
     */
    public function __construct()
    {
        parent::__construct($this->items());
    }

    /**
     * Refresh the configuration values.
     */
    public function refresh(): void
    {
        $this->items = $this->items();
    }

    // --- Company Setup: General settings ---

    public function companyName(): ?string
    {
        return $this->get('coy_name', null);
    }

    public function postalAddress(): ?string
    {
        return $this->get('postal_address', null);
    }

    public function domicile(): ?string
    {
        return $this->get('domicile', null);
    }

    public function phone(): ?string
    {
        return $this->get('phone', null);
    }

    public function fax(): ?string
    {
        return $this->get('fax', null);
    }

    public function email(): ?string
    {
        return $this->get('email', null);
    }

    public function bccEmail(): ?string
    {
        return $this->get('bcc_email', null);
    }

    public function companyNumber(): ?string
    {
        return $this->get('coy_no', null);
    }

    public function taxRegistrationNumber(): ?string
    {
        return $this->get('gst_no', null);
    }

    public function homeCurrency(): ?string
    {
        return $this->get('curr_default', null);
    }

    public function companyLogoPath(): ?string
    {
        return $this->get('coy_logo', null);
    }

    public function showCompanyLogoOnReports(): bool
    {
        return (bool) $this->get('company_logo_report', false);
    }

    public function enableBarcodeGenerationForItems(): bool
    {
        return (bool) $this->get('barcodes_on_stock', false);
    }

    public function autoIncreaseReferenceNumber(): bool
    {
        return (bool) $this->get('ref_no_auto_increase', false);
    }

    public function useDimensionOnRecurrentInvoice(): bool
    {
        return (bool) $this->get('dim_on_recurrent_invoice', false);
    }

    public function useLongDescriptionInInvoice(): bool
    {
        return (bool) $this->get('long_description_invoice', false);
    }

    public function showCompanyLogoOnViews(): bool
    {
        return (bool) $this->get('company_logo_on_views', false);
    }

    public function versionId(): ?string
    {
        return $this->get('version_id', null);
    }

    // --- Company Setup: General Ledger Settings ---

    public function fiscalYear(): ?int
    {
        return ((int) $this->get('f_year', null)) ?: null;
    }

    public function taxPeriodInMonths(): int
    {
        return (int) $this->get('tax_prd', 0);
    }

    public function taxLastPeriod(): int
    {
        return (int) $this->get('tax_last', 0);
    }

    public function showTaxInclusiveBreakdown(): bool
    {
        return (bool) $this->get('alternative_tax_include_on_docs', false);
    }

    public function suppressTaxRateOnDocuments(): bool
    {
        return (bool) $this->get('suppress_tax_rates', false);
    }

    public function autoRevalueCurrencies(): bool
    {
        return (bool) $this->get('auto_curr_reval', false);
    }

    // --- Company Setup: Sales Pricing ---

    /**
     * Sales type ID used as base for auto price calculations. -1 means not set.
     */
    public function baseSalesTypeId(): int
    {
        return (int) $this->get('base_sales', -1);
    }

    /**
     * Percentage to add to standard cost when calculating price.
     * -1 means disabled.
     */
    public function stdCostMarkupPercentage(): int
    {
        return (int) $this->get('add_pct', -1);
    }

    /**
     * Round calculated prices to nearest increment (e.g. 5 for nearest 5 cents).
     */
    public function itemPriceRoundToMinorUnit(): int
    {
        $value = (int) $this->get('round_to', 1);
        return ($value <= 0) ? 1 : $value;
    }

    // --- Company Setup: Optional Modules ---

    public function useManufacturingModule(): bool
    {
        return (bool) $this->get('use_manufacturing', false);
    }

    public function useFixedAssetsModule(): bool
    {
        return (bool) $this->get('use_fixed_assets', false);
    }

    public function dimensionLevel(): int
    {
        return (int) $this->get('use_dimension', 0);
    }

    // --- Company Setup: User Interface Options ---

    /**
     * When true, dropdown lists show both short name and full name.
     */
    public function displayCodeAndNameInDropdown(): bool
    {
        return (bool) $this->get('shortname_name_in_list', false);
    }

    public function printDirectly(): bool
    {
        return (bool) $this->get('print_dialog_direct', false);
    }

    public function hideItemsInDropdown(): bool
    {
        return (bool) $this->get('no_item_list', false);
    }

    public function hideCustomersInDropdown(): bool
    {
        return (bool) $this->get('no_customer_list', false);
    }

    public function hideSuppliersInDropdown(): bool
    {
        return (bool) $this->get('no_supplier_list', false);
    }

    public function sessionIdleTimeoutSeconds(): int
    {
        return (int) $this->get('login_tout', 0);
    }

    public function transactionDropdownLimitDays(): int
    {
        return (int) $this->get('max_days_in_docs', 0);
    }

    // --- GL Setup: General GL ---

    public function agingBucketSizeDays(): int
    {
        return (int) $this->get('past_due_days', 0);
    }

    public function accountCodeFormat(): AccountCodeFormat
    {
        return AccountCodeFormat::from((int) $this->get('accounts_alpha', 0));
    }

    public function retainedEarningsAccount(): ?string
    {
        return $this->get('retained_earnings_act', null);
    }

    public function profitOrLossAccount(): ?string
    {
        return $this->get('profit_loss_year_act', null);
    }

    public function exchangeVarianceAccount(): ?string
    {
        return $this->get('exchange_diff_act', null);
    }

    public function bankChargeAccount(): ?string
    {
        return $this->get('bank_charge_act', null);
    }

    public function taxTotalingAlgorithm(): TaxAlgorithm
    {
        return TaxAlgorithm::from((int) $this->get(
            'tax_algorithm',
            TaxAlgorithm::SUM_THEN_CALCULATE->value
        ));
    }

    public function glClosingDate(): ?string
    {
        return $this->get('gl_closing_date', null);
    }

    // --- GL Setup: Dimension Defaults ---

    public function dimensionRequiredByDays(): int
    {
        return (int) $this->get('default_dim_required', 0);
    }

    // --- GL Setup: Customers and Sales ---

    public function creditLimit(): int
    {
        return (int) $this->get('default_credit_limit', 0);
    }

    public function printTransNumberOverReference(): bool
    {
        return (bool) $this->get('print_invoice_no', false);
    }

    public function accumulatesShipping(): bool
    {
        return (bool) $this->get('accumulate_shipping', false);
    }

    public function printItemImageOnQuote(): bool
    {
        return (bool) $this->get('print_item_images_on_quote', false);
    }

    public function legalText(): ?string
    {
        return $this->get('legal_text', null);
    }

    public function freightAccount(): ?string
    {
        return $this->get('freight_act', null);
    }

    public function deferredIncomeAccount(): ?string
    {
        return $this->get('deferred_income_act', null);
    }

    // --- GL Setup: Customers and Sales Defaults ---

    public function receivableAccount(): ?string
    {
        return $this->get('debtors_act', null);
    }

    public function customerSalesAccount(): ?string
    {
        return $this->get('default_sales_act', null);
    }

    public function salesDiscountAccount(): ?string
    {
        return $this->get('default_sales_discount_act', null);
    }

    public function cashDiscountAccount(): ?string
    {
        return $this->get('default_prompt_payment_act', null);
    }

    public function quoteValidForDays(): int
    {
        return (int) $this->get('default_quote_valid_days', 0);
    }

    public function deliveryRequiredByDays(): int
    {
        return (int) $this->get('default_delivery_required', 0);
    }

    // --- GL Setup: Marketplace Sales Defaults ---

    public function marketplaceCommissionAccount(): ?string
    {
        return $this->get('marketplace_commission_act', null);
    }

    public function marketplaceShippingAccount(): ?string
    {
        return $this->get('marketplace_shipping_act', null);
    }

    public function marketplaceExpenseItems(): array
    {
        return array_filter(explode(',', $this->get('marketplace_expense_items', '')));
    }

    // --- GL Setup: Suppliers and Purchasing ---

    public function purchaseOrderOverReceiveAllowancePercentage(): int
    {
        return (int) $this->get('po_over_receive', 0);
    }

    public function purchaseOrderOverChargeAllowancePercentage(): int
    {
        return (int) $this->get('po_over_charge', 0);
    }

    // --- GL Setup: Suppliers and Purchasing Defaults ---

    public function payableAccount(): ?string
    {
        return $this->get('creditors_act', null);
    }

    public function discountReceiveAccount(): ?string
    {
        return $this->get('pyt_discount_act', null);
    }

    public function goodsReceiveClearingAccount(): ?string
    {
        return $this->get('grn_clearing_act', null);
    }

    public function goodsReceiveRequiredByDays(): int
    {
        return (int) $this->get('default_receival_required', 0);
    }

    public function showItemCodeInPurchaseOrder(): bool
    {
        return (bool) $this->get('show_po_item_codes', false);
    }

    // --- GL Setup: Inventory ---

    public function allowNegativeStock(): bool
    {
        return (bool) $this->get('allow_negative_stock', false);
    }

    public function suppressZeroLineServicesInReports(): bool
    {
        return (bool) $this->get('no_zero_lines_amount', false);
    }

    public function enableLocationNotification(): bool
    {
        return (bool) $this->get('loc_notification', false);
    }

    public function allowNegativePrices(): bool
    {
        return (bool) $this->get('allow_negative_prices', false);
    }

    // --- GL Setup: Items Defaults ---

    public function itemSalesAccount(): ?string
    {
        return $this->get('default_inv_sales_act', null);
    }

    public function inventoryAccount(): ?string
    {
        return $this->get('default_inventory_act', null);
    }

    public function cogsAccount(): ?string
    {
        return $this->get('default_cogs_act', null);
    }

    public function adjustmentAccount(): ?string
    {
        return $this->get('default_adj_act', null);
    }

    public function workInProgressAccount(): ?string
    {
        return $this->get('default_wip_act', null);
    }

    // --- GL Setup: Fixed Assets Defaults ---

    public function lossOnAssetDisposalAccount(): ?string
    {
        return $this->get('default_loss_on_asset_disposal_act', null);
    }

    public function depreciationPeriodType(): DepreciationPeriodType
    {
        return DepreciationPeriodType::from((int) $this->get(
            'depreciation_period',
            DepreciationPeriodType::MONTHLY->value
        ));
    }

    // --- GL Setup: Manufacturing Defaults ---

    public function workOrderRequiredByDays(): int
    {
        return (int) $this->get('default_workorder_required', 0);
    }
}
