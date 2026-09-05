<?php

namespace App\Foundation\Component\Table\Support;

/**
 * A length a table may be declared in: a number and one unit, or a bare nought.
 *
 * A closed list rather than a parser, holding only units the oldest engines in the field parse: an
 * unparseable unit is not degraded, it drops the whole declaration holding it.
 */
final class Length
{
    /**
     * @var list<string> the units a table's own arithmetic can be carried out in
     */
    private const UNITS = ['px', 'em', 'rem', 'ex', 'ch', 'vw', 'vh', 'vmin', 'vmax', '%', 'pt', 'pc', 'cm', 'mm', 'in'];

    /**
     * Nought is the one value needing no unit, being a distance rather than an extent.
     */
    public static function measurable(string $value): bool
    {
        if ($value === '0') {
            return true;
        }

        $units = implode('|', array_map(fn (string $unit) => preg_quote($unit, '/'), self::UNITS));

        return preg_match('/^(?:\d+(?:\.\d+)?|\.\d+)(?:'.$units.')$/', $value) === 1;
    }
}
