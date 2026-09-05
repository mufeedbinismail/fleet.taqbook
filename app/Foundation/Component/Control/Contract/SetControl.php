<?php

namespace App\Foundation\Component\Control\Contract;

/**
 * A control answering with several values at once, which is a different answer to one of them
 * rather than a way of giving one.
 */
interface SetControl extends Control
{
    /**
     * @return list<mixed>|null
     */
    public function read(mixed $raw): ?array;
}
