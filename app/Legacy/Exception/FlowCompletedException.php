<?php

namespace App\Legacy\Exception;

use App\Legacy\Exception\FlowControlException as Exception;

class FlowCompletedException extends Exception
{
    public function __construct()
    {
        parent::__construct();
    }
}
