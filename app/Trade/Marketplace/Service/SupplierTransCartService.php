<?php

namespace App\Trade\Marketplace\Service;

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Repository\TaxRepository;
use App\Finance\Tax\Service\TaxService;
use App\Finance\Tax\ValueObject\TaxBreakdown;
use App\Trade\Marketplace\Cart\DraftSupplierTransLine;
use App\Trade\Marketplace\Cart\SupplierTransCart;
use App\Trade\Marketplace\Cart\SupplierTransCartLine;
use App\Trade\Marketplace\Query\Marketplace\MarketplaceQuery;
use Brick\Math\BigDecimal;

class SupplierTransCartService
{
    public function __construct(
        private MarketplaceQuery $marketplaceQuery,
        private TaxRepository    $taxRepository,
        private TaxService       $taxService,
    ) {}

    public function setMarketplace(SupplierTransCart $cart, ?string $marketplaceId): void
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

    public function addLine(SupplierTransCart $cart, DraftSupplierTransLine $draft): void
    {
        $itemTaxSetting = $this->taxRepository->getItemTaxSetting($draft->stockId);
        $line = new SupplierTransCartLine(
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

    public function updateLine(SupplierTransCart $cart, int $index, DraftSupplierTransLine $draft): void
    {
        if (!isset($cart->line_items[$index])) return;

        $line         = $cart->line_items[$index];
        $line->qty    = BigDecimal::of($draft->qty);
        $line->amount = MoneyFactory::of($draft->amount);
        if ($cart->marketplaceTaxSetting) {
            $line->taxBreakdown = $this->taxService->getTaxBreakdownForSource($line, $cart->marketplaceTaxSetting);
        }
    }

    public function recalculateAllTaxes(SupplierTransCart $cart): void
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
