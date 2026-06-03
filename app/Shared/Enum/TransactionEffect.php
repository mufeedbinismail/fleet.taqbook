<?php

namespace App\Shared\Enum;

enum TransactionEffect: int
{
    case Increase =  1;
    case Decrease = -1;
    case NoEffect =  0;
}
