<?php

namespace App\Foundation\Auth\Http\Controller;

use App\Foundation\Auth\Action\LoginAction;
use App\Foundation\Auth\Action\LogoutAction;
use App\Foundation\Auth\Http\Request\LoginRequest;
use App\Foundation\Framework\Http\Controller\Controller;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user() !== null) {
            return redirect()->intended($this->defaultTarget());
        }

        return view('pages.auth.login');
    }

    public function login(LoginRequest $request, LoginAction $action)
    {
        $intent = $request->toIntent();

        $this->refuse($action->validate($intent));

        $action->execute($intent, $request->session());

        return redirect()->intended($this->defaultTarget());
    }

    public function logout(Request $request, LogoutAction $action)
    {
        $action->execute($request->session());

        return view('pages.auth.logout');
    }

    /**
     * Deliberately the home address and nothing more specific. Choosing a destination is a decision
     * about where this user belongs, and neither their preference nor what they may open is in view
     * from here.
     */
    protected function defaultTarget(): string
    {
        return legacy_url('/index.php');
    }
}
