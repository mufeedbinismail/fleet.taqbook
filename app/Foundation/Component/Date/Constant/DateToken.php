<?php

namespace App\Foundation\Component\Date\Constant;

final class DateToken
{
    /**
     * The tokens PHP spells a day, a month or a year with.
     *
     * @var list<string>
     */
    public const DATE = ['d', 'j', 'm', 'n', 'M', 'F', 'Y', 'y'];

    /**
     * The tokens PHP spells a time of day with.
     *
     * @var list<string>
     */
    public const TIME = ['H', 'G', 'h', 'g', 'i', 's', 'a', 'A'];

    public static function carriesDate(string $format): bool
    {
        return self::spells($format, self::DATE);
    }

    public static function carriesTime(string $format): bool
    {
        return self::spells($format, self::TIME);
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function spells(string $format, array $tokens): bool
    {
        for ($at = 0; $at < strlen($format); $at++) {
            // A backslash makes a letter of whatever follows it, so the pair is stepped over
            // whole — and the escape can itself be escaped, so `\\H` ends in a token that counts.
            if ($format[$at] === '\\') {
                $at++;

                continue;
            }

            if (in_array($format[$at], $tokens, true)) {
                return true;
            }
        }

        return false;
    }
}
