<?php

namespace App\Foundation\Navigation\Enum;

/**
 * Which side of a two-column layout a destination is drawn on.
 *
 * Two cases rather than N, because a two-column grid is the only shape that has ever needed to be
 * described. Growing this means deciding what a view already laying out two columns does with a
 * third, which is a question worth answering when something actually asks it.
 */
enum Column: string
{
    case Left = 'left';
    case Right = 'right';
}
