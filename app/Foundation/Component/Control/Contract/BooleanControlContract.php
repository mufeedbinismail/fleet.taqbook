<?php

namespace App\Foundation\Component\Control\Contract;

/**
 * A control answering with one of two states, where off is a state and null the absence of one.
 */
interface BooleanControlContract extends ControlContract
{
    public function read(mixed $raw): ?bool;
}
