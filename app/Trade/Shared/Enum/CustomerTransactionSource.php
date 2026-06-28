<?php

namespace App\Trade\Shared\Enum;

enum CustomerTransactionSource: int
{
    case Manual            = 1;
    case MarketplaceManual = 2;
}
