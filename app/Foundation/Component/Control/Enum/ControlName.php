<?php

namespace App\Foundation\Component\Control\Enum;

/**
 * The controls a value may be asked for by, named once so that one word cannot come to mean two
 * things.
 */
enum ControlName: string
{
    case Text = 'text';

    case Select = 'select';

    /** A set too large to list, its choices fetched as they are searched for. */
    case Lookup = 'lookup';

    case MultiSelect = 'multiSelect';

    case Toggle = 'toggle';

    case Date = 'date';

    case DateRange = 'dateRange';
}
