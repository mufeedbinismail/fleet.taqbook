<?php

namespace App\Foundation\Http\Controller;

use App\Foundation\Http\Request\LoginRequest;
use App\Foundation\Service\UserAuthenticator;
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

    protected function defaultTarget(): string
    {
        return legacy_url('/index.php', ['application' => 'orders']);
    }
}
