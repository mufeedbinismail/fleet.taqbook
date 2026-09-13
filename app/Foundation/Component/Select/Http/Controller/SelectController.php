<?php

namespace App\Foundation\Component\Select\Http\Controller;

use App\Foundation\Component\Select\Http\Request\OptionSearchRequest;
use App\Foundation\Component\Select\Http\Response\SelectResponse;
use App\Foundation\Component\Select\Repository\OptionRepository;
use App\Foundation\Framework\Http\Controller\Controller;

/**
 * The one controller behind every option list.
 *
 * Which rows are offered is the route's decision, carried as the definition it names. Reading the
 * query string and shaping the answer belong to the control rather than to whoever owns the rows,
 * which is why neither is written a second time per screen.
 */
class SelectController extends Controller
{
    public function __invoke(OptionSearchRequest $request, OptionRepository $options): SelectResponse
    {
        return SelectResponse::of($options->page($request->select(), $request->toState()));
    }
}
