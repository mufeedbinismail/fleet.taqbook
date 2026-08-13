<?php

namespace App\Foundation\Component\Select\Support;

/**
 * The extra columns one row carries into the markup as `data-` attributes.
 *
 * Keys are held to lower case, digits and underscores for two reasons, and relaxing the set needs
 * both answered. It is the form a `data-` attribute survives a round trip in — a dash in the name
 * comes back camel-cased off the element's dataset, so a row written and then read back again would
 * answer under a key nobody wrote. It is also the whole of what stands between this array and the
 * markup: a name is interpolated into an attribute position, where escaping does not reach.
 *
 * Values flatten to strings, an attribute holding nothing else. Anything that will not flatten is
 * dropped rather than written as the word an array or an object stringifies to, which would read on
 * the far side as a value somebody meant.
 */
class DataAttributes
{
    /**
     * Names an option carries in its own right, and so ones its extras may not claim: a row using
     * one would be saying two different things under a single name, and whichever was written
     * second is the one anybody asking would get.
     */
    private const RESERVED = ['description'];

    /**
     * @return array<string, string>
     */
    public static function of(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $clean = [];

        foreach ($data as $key => $value) {
            if (! is_string($key) || preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1) {
                continue;
            }

            if (in_array($key, self::RESERVED, true) || ! is_scalar($value)) {
                continue;
            }

            $clean[$key] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }

        return $clean;
    }
}
