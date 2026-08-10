<?php

namespace App\Foundation\Framework\Http\Controller;

use App\Foundation\Framework\Concern\RefusesValidationResultConcern;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, RefusesValidationResultConcern, ValidatesRequests;
}
