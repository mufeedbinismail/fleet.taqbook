<?php

namespace App\Foundation\Component\Table\Enum;

/**
 * The edge of the grid a column is pinned against.
 *
 * Named for where a row begins and ends rather than for a side, since which side that is depends
 * on the reading direction and a column is declared once for both.
 */
enum Stick: string
{
    case Start = 'start';

    case End = 'end';
}
