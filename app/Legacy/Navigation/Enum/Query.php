<?php

namespace App\Legacy\Navigation\Enum;

/**
 * Marks a query parameter as one that must be present, whatever its value.
 *
 * An enum case rather than a `'*'` string because it sits in the value position of a query array,
 * where any string is a value some page could genuinely receive.
 */
enum Query
{
    case ANY;
}
