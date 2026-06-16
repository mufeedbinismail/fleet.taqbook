<?php

namespace App\Trade\Sale\Repository;

use App\Trade\Sale\Model\CustomerBranch;

class CustomerRepository
{
    public function getDefaultBranch(int $customerId): CustomerBranch
    {
        $branch = CustomerBranch::where('debtor_no', $customerId)->first();
        if (!$branch) {
            throw new \DomainException("No branch found for customer ID: $customerId");
        }
        return $branch;
    }

    public function getBranch(int $customerId, int $branchId)
    {
        $branch = CustomerBranch::where('debtor_no', $customerId)
            ->where('branch_code', $branchId)
            ->first();

        if (!$branch) {
            throw new \DomainException(sprintf("No branch found for customer ID: %d, branch ID: %d", $customerId, $branchId));
        }

        return $branch;
    }

    public function getReceivableAccount(int $customerId, ?int $branchId = null): string
    {
        if (is_null($branchId)) {
            $branch = $this->getDefaultBranch($customerId);
        } else {
            $branch = $this->getBranch($customerId, $branchId);
        }

        return $branch->receivables_account;
    }
}