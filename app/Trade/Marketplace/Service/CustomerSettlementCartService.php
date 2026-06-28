<?php

namespace App\Trade\Marketplace\Service;

use App\Trade\Marketplace\Cart\CustomerSettlementCart;
use App\Trade\Shared\Collection\DraftAllocationLineCollection;
use App\Trade\Marketplace\Query\Marketplace\MarketplaceQuery;
use App\Trade\Sale\Repository\CustomerRepository;
use App\Shared\DTO\ValidationResult;
use App\Trade\Marketplace\Repository\CustAllocRepository;

class CustomerSettlementCartService
{
    public function __construct(
        private MarketplaceQuery       $marketplaceQuery,
        private CustAllocRepository    $custAllocRepository,
        private CustomerRepository     $customerRepository
    ) {}

    /**
     * Set the customer on the cart, resolve its receivable account (via its default
     * branch), and rebuild the eligible-invoice line set. No-op if the customer didn't
     * change.
     */
    public function setCustomer(CustomerSettlementCart $cart, ?int $customerId): void
    {
        if ($cart->customerId === $customerId) {
            return;
        }

        $cart->customerId = $customerId;

        $branchId = $customerId
            ? $this->customerRepository->getDefaultBranch($customerId)->getKey()
            : null;
        $this->setBranch($cart, $branchId);

        $this->refreshLines($cart);
    }

    private function setBranch(CustomerSettlementCart $cart, ?int $branchId): void
    {
        $cart->branchId          = $branchId;
        $cart->receivableAccount = ($cart->customerId && $branchId)
            ? $this->customerRepository->getReceivableAccount($cart->customerId, $branchId)
            : null;
    }

    /**
     * Set the marketplace (and the supplier/payable account it resolves to) on
     * the cart, then rebuild the eligible-invoice line set. No-op if unchanged.
     */
    public function setMarketplace(CustomerSettlementCart $cart, ?int $marketplaceId): void
    {
        if ($cart->marketplaceId === $marketplaceId) {
            return;
        }

        $cart->marketplaceId = $marketplaceId;
        $this->resolveMarketplaceAccounts($cart);
        $this->refreshLines($cart);
    }

    private function resolveMarketplaceAccounts(CustomerSettlementCart $cart): void
    {
        if ($cart->marketplaceId) {
            $marketplace = $this->marketplaceQuery->builder($cart->marketplaceId)->first();

            $cart->supplierId     = $marketplace?->supplier_id ? (int) $marketplace->supplier_id : null;
            $cart->payableAccount = $marketplace?->payable_account;
        } else {
            $cart->supplierId     = null;
            $cart->payableAccount = null;
        }
    }

    /**
     * Repopulate the cart's allocation lines from current DB state for the selected
     * customer + marketplace. Every line starts unselected. This is the ONLY place
     * that reads invoice state into the cart — checkbox toggling afterwards is
     * a pure cart mutation (see applySelection), and the write path re-validates
     * once via validateFreshness rather than re-reading per keystroke.
     */
    public function refreshLines(CustomerSettlementCart $cart): void
    {
        $cart->lines = $this->getFreshLines($cart);
    }

    public function getFreshLines(CustomerSettlementCart $cart, bool $lock = false): DraftAllocationLineCollection
    {
        return $this->custAllocRepository->getInvoiceAllocatees(
            $cart->customerId,
            $cart->marketplaceId,
            $cart->transId->isExisting() ? $cart->transId : null,
            $lock
        );
    }

    /**
     * Re-read invoice state under a row lock and confirm every selected line still
     * matches what the user acted on: the invoice is still eligible, and neither its
     * total nor its already-allocated amount has shifted since the cart was loaded.
     * Must run inside the same transaction as the write so the lock is held through it.
     */
    public function validateFreshness(CustomerSettlementCart $cart): ValidationResult
    {
        $fresh = $this->getFreshLines($cart, lock: true);

        foreach ($cart->selectedLines() as $line) {
            $current = $fresh[$line->transId->toString()] ?? null;

            if (!$current) {
                return ValidationResult::error(null, __(
                    'Invoice #:n is no longer open for allocation. Reload the page and try again.',
                    ['n' => $line->transId->id]
                ));
            }

            if (!$current->total->isEqualTo($line->total) || !$current->allocated->isEqualTo($line->allocated)) {
                return ValidationResult::error(null, __(
                    'Invoice #:n changed since the page was loaded. Reload the page and try again.',
                    ['n' => $line->transId->id]
                ));
            }
        }

        return ValidationResult::success();
    }
}
