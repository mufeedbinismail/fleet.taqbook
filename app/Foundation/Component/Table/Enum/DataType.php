<?php

namespace App\Foundation\Component\Table\Enum;

/**
 * The primitive shape a field's values share: what a value is, never what it means.
 */
enum DataType: string
{
    case Text = 'text';

    case Date = 'date';

    case DateTime = 'datetime';

    case Money = 'money';

    case Boolean = 'boolean';

    public function align(): string
    {
        return match ($this) {
            self::Money => 'right',
            self::Boolean => 'center',
            default => 'left',
        };
    }
}
