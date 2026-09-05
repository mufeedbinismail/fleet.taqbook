<?php

namespace App\Foundation\Component\Control\Contract;

use App\Foundation\Shared\ValueObject\DomainDateTime;

/**
 * A control answering with a day, always at the start of it, so that two answers compare as the
 * days they name rather than as the moments they were read at.
 */
interface DateControl extends Control
{
    public function read(mixed $raw): ?DomainDateTime;
}
