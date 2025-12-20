<?php

namespace App\Legacy\Exception;

use App\Legacy\Exception\FlowControlException as Exception;

class FlowTerminatedException extends Exception
{
    public function __construct()
    {
        parent::__construct();
    }
}