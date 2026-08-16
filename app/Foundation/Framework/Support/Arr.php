<?php

namespace App\Foundation\Framework\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Arr extends \Illuminate\Support\Arr
{
    const NOT_SET = '__NOT_SET__';

    /**
     * A value as the array it can be read as, or null where it cannot be read as one at all.
     *
     * A value that says how it wants to become an array is answered on its own terms rather than
     * by having its properties read off it, so a class that hides or renames what it exposes is
     * not quietly undone here.
     */
    public static function from(mixed $value): ?array
    {
        return match (true) {
            is_array($value) => $value,
            $value instanceof Arrayable => $value->toArray(),
            $value instanceof JsonSerializable => (array) $value->jsonSerialize(),
            is_object($value) => (array) $value,
            default => null,
        };
    }

    /**
     * Get the value of a key from an array of key-value pairs.
     */
    public static function kvGet($items, $key, $default = self::NOT_SET)
    {
        $value = static::get($items, $key, '');

        if ($value === '' && $default !== self::NOT_SET) {
            return $default;
        }

        return $value;
    }

    public static function bracketGet(array $array, string $key, mixed $default = null): mixed
    {
        $keys = preg_split('/\[|\]\[|\]/', $key, -1, PREG_SPLIT_NO_EMPTY);
        $current = $array;
        foreach ($keys as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public static function bracketSet(array &$array, string $key, mixed $value): void
    {
        $keys = preg_split('/\[|\]\[|\]/', $key, -1, PREG_SPLIT_NO_EMPTY);
        $current = &$array;
        foreach ($keys as $segment) {
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        $current = $value;
    }
}
