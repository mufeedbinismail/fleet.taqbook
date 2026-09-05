<?php

namespace App\Foundation\Component\Table\Support;

/**
 * Escaping for terms that end up on the right of a LIKE.
 *
 * Without it a `_` typed into a search box matches any character and a lone `%` matches every row,
 * so a user searching for something they can see in front of them gets results that have nothing to
 * do with it.
 */
final class LikeExpression
{
    /**
     * The backslash goes first: escaping it after the wildcards would escape the backslashes this
     * very method had just introduced.
     *
     * Backslash is the escape character MariaDB assumes when a LIKE carries no ESCAPE clause.
     */
    public static function escape(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    public static function contains(string $term): string
    {
        return '%'.self::escape($term).'%';
    }
}
