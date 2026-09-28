<?php

namespace App\Foundation\Component\Control\Contract;

interface ScalarControlContract extends ControlContract
{
    public function read(mixed $raw): int|float|string|bool|null;
}
