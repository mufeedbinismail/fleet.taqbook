<?php

namespace App\Legacy\Enum;

enum PrintDestination: int
{
    case PDF_PRINTER = 0;
    case EXCEL = 1;
}
