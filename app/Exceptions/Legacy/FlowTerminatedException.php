<?php

namespace App\Exceptions\Legacy;

use App\Exceptions\Legacy\FlowControlException as Exception;

class FlowTerminatedException extends Exception
{
    public function __construct()
    {
        parent::__construct();
    }
}