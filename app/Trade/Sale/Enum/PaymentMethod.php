<?php

namespace App\Trade\Sale\Enum;

enum PaymentMethod: int
{
    case Default           = 1;
    case MarketplaceSetoff = 2;
}
