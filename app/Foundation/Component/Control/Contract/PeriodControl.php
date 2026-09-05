<?php

namespace App\Foundation\Component\Control\Contract;

use App\Foundation\Shared\ValueObject\Period;

/**
 * A control answering with a span, which one end alone is enough to make.
 */
interface PeriodControl extends Control
{
    public function read(mixed $raw): ?Period;
}
