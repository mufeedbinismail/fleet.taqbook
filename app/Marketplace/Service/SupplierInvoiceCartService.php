<?php

namespace App\Marketplace\Service;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Repository\TaxRepository;
use App\Finance\Tax\Service\TaxService;
use App\Finance\Tax\ValueObject\TaxBreakdown;
use App\Marketplace\Cart\DraftSupplierInvoiceLine;
use App\Marketplace\Cart\SupplierInvoiceCart;
use App\Marketplace\Cart\SupplierInvoiceCartLine;
use App\Marketplace\Query\Marketplace\MarketplaceQuery;
use Brick\Math\BigDecimal;

class SupplierInvoiceCartService
{
    public function __construct(
        private MarketplaceQuery $marketplaceQuery,
        private TaxRepository    $taxRepository,
        private TaxService       $taxService,
    ) {}

    public function setMarketplace(SupplierInvoiceCart $cart, ?string $marketplaceId): void
    {
        if (!$marketplaceId) {
            $cart->marketplaceId        = null;
            $cart->supplierId           = null;
            $cart->provisionAccount     = null;
            $cart->payableAccount       = null;
            $cart->marketplaceTaxSetting = null;
        } else {
            $marketplace = $this->marketplaceQuery->builder($marketplaceId)->first();
            $cart->marketplaceId        = $marketplaceId;
            $cart->supplierId           = (int) $marketplace->supplier_id;
            $cart->provisionAccount     = $marketplace->provision_account;
            $cart->payableAccount       = $marketplace->payable_account;
            $cart->marketplaceTaxSetting = $this->taxRepository->getTaxSetting(
                $marketplace->tax_group_id,
                $marketplace->tax_included
            );
        }

        $this->recalculateAllTaxes($cart);
    }

    public function addLine(SupplierInvoiceCart $cart, DraftSupplierInvoiceLine $draft): void
    {
        $itemTaxSetting = $this->taxRepository->getItemTaxSetting($draft->stockId);
        $line = new SupplierInvoiceCartLine(
            $draft->stockId,
            $draft->description,
            $draft->unit,
            BigDecimal::of($draft->qty),
            MoneyFactory::of($draft->amount),
            $itemTaxSetting
        );
        if ($cart->marketplaceTaxSetting) {
            $line->taxBreakdown = $this->taxService->getTaxBreakdownForSource($line, $cart->marketplaceTaxSetting);
        }
        $cart->addLine($line);
    }

    public function updateLine(SupplierInvoiceCart $cart, int $index, DraftSupplierInvoiceLine $draft): void
    {
        if (!isset($cart->line_items[$index])) return;

        $line         = $cart->line_items[$index];
        $line->qty    = BigDecimal::of($draft->qty);
        $line->amount = MoneyFactory::of($draft->amount);
        if ($cart->marketplaceTaxSetting) {
            $line->taxBreakdown = $this->taxService->getTaxBreakdownForSource($line, $cart->marketplaceTaxSetting);
        }
    }

    public function recalculateAllTaxes(SupplierInvoiceCart $cart): void
    {
        if (!$cart->marketplaceTaxSetting) {
            foreach ($cart->line_items as $line) {
                $line->taxBreakdown = new TaxBreakdown(MoneyFactory::zero(), $line->toTaxableItem()->price);
            }
        } else {
            foreach ($cart->line_items as $line) {
                $line->taxBreakdown = $this->taxService->getTaxBreakdownForSource($line, $cart->marketplaceTaxSetting);
            }
        }
    }
}
