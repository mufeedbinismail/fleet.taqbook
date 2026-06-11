<?php

namespace App\Trade\Shared\Enum;

enum SupplierTransactionSource: int
{
    case Manual              = 1;
    case MarketplaceManual = 2;
}
