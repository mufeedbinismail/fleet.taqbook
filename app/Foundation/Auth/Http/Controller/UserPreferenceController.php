<?php

namespace App\Foundation\Auth\Http\Controller;

use App\Foundation\Auth\Http\Request\SaveSkinRequest;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\Http\Controller\Controller;
use Illuminate\Http\Response;

class UserPreferenceController extends Controller
{
    public function __construct(protected UserRepository $users) {}

    public function skin(SaveSkinRequest $request): Response
    {
        $this->users->saveSkin($request->user()->id, $request->toSkin());

        return response()->noContent();
    }
}
