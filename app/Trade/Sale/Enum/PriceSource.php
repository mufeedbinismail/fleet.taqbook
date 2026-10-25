<?php

namespace App\Trade\Sale\Enum;

/**
 * How a sale price was arrived at, from the price stored for the sales type and currency down to the cost markup.
 */
enum PriceSource: string
{
    case Stored = 'stored';
    case Base = 'base';
    case Converted = 'converted';
    case ConvertedBase = 'converted_base';
    case Markup = 'markup';
    case Kit = 'kit';
}
