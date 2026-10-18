<?php

namespace App\Finance\Ledger\Enum;

enum ChartClass: int
{
    case ASSETS = 1;
    case LIABILITIES = 2;
    case EQUITY = 3;
    case INCOME = 4;
    case COST_OF_SALES = 5;
    case EXPENSE = 6;
}
