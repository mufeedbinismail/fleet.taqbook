<?php

namespace App\Foundation\Auth\Constant;

/**
 * What a role name or a login may be; the system seeds its own rows under the reserved prefix.
 */
final class AccessName
{
    // ASCII only: the accent-insensitive collation would let an accented lookalike collide with a reserved name.
    public const PATTERN = '/^[A-Za-z0-9]+([ _&-][A-Za-z0-9]+)*$/';

    public const RESERVED_PREFIX = 'TB-';
}
