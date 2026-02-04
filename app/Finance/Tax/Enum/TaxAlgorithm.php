<?php

namespace App\Finance\Tax\Enum;

enum TaxAlgorithm: int
{
    case SUM_THEN_CALCULATE = 1;  // total taxes calculated from the cumulative sum of line totals
    case CALCULATE_THEN_SUM = 2;  // taxes calculated for each line separately, then summed
}
