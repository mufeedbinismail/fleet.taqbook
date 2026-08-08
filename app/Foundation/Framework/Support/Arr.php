<?php

namespace App\Foundation\Framework\Support;

class Arr extends \Illuminate\Support\Arr
{
    const NOT_SET = '__NOT_SET__';

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
            if (!is_array($current) || !array_key_exists($segment, $current))
                return $default;
            $current = $current[$segment];
        }
        return $current;
    }

    public static function bracketSet(array &$array, string $key, mixed $value): void
    {
        $keys = preg_split('/\[|\]\[|\]/', $key, -1, PREG_SPLIT_NO_EMPTY);
        $current = &$array;
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment]))
                $current[$segment] = [];
            $current = &$current[$segment];
        }
        $current = $value;
    }
}
