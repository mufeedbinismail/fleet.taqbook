<?php

namespace App\Foundation\Auth\Http\Controller;

use App\Foundation\Auth\Http\Request\LoginRequest;
use App\Foundation\Auth\Service\UserAuthenticator;
use App\Foundation\Http\Controller\Controller;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    public function show(UserAuthenticator $authenticator)
    {
        if ($authenticator->check()) {
            return redirect()->intended($this->defaultTarget());
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request, UserAuthenticator $authenticator)
    {
        $authenticator->authenticate($request);

        return redirect()->intended($this->defaultTarget());
    }

    public function logout(Request $request, UserAuthenticator $authenticator)
    {
        $authenticator->logout($request);

        return view('auth.logout');
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
