<?php

namespace App\Foundation\Auth\Action;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

class LogoutAction
{
    public function execute(Session $session): void
    {
        Auth::guard()->logout();

        $session->invalidate();
        $session->regenerateToken();
    }
}
