<?php

namespace App\Shared\Enum;

enum SupplierTransactionSource: int
{
    case Manual              = 1;
    case MarketplaceManual = 2;
}
