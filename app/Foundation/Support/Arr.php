<?php

namespace App\Foundation\Support;


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
}