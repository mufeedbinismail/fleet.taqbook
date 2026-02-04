<?php

namespace App\Legacy\Enum;

enum AccountCodeFormat: int
{
    case NUMERIC = 0;
    case ALPHA_NUMERIC = 1;
    case ALPHA_NUMERIC_UPPER = 2;
}
