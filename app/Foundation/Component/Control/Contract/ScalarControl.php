<?php

namespace App\Foundation\Component\Control\Contract;

interface ScalarControl extends Control
{
    public function read(mixed $raw): int|float|string|bool|null;
}
