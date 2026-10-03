<?php

namespace App\Trust\Support;

final class Encoding
{
    private const VARIANT = SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

    public static function encode(string $bytes): string
    {
        return sodium_bin2base64($bytes, self::VARIANT);
    }

    /** An empty string decodes clean rather than throwing. */
    public static function decode(string $encoded): string
    {
        return sodium_base642bin($encoded, self::VARIANT);
    }
}
