<?php

use App\Foundation\Shared\Enum\DateFormat;
use App\Foundation\Shared\Enum\DateSeparator;
use App\Foundation\Shared\Enum\DateSystem;

/*
 | ------------------------------------------------------------------------------------------
 | How dates are written where nobody has said otherwise.
 |
 | Only what an installation may actually decide. Each value is an id, and the set it indexes
 | into is not repeated here: a copy of a closed set is editable, so it can disagree with the set
 | itself — at which point one of the two decides what is accepted and the other decides what is
 | shown, and nothing says which is which.
 | ------------------------------------------------------------------------------------------
*/
return [

    'calendar_system_id' => DateSystem::Traditional->value,

    'format_id' => DateFormat::DDMMYYYY->value,

    'separator_id' => DateSeparator::SLASH->value,

];
