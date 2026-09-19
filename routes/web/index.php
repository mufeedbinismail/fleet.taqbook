<?php

use App\Foundation\Auth\Http\Middleware\IdleTimeout;
use Illuminate\Support\Facades\Route;

// Files are required one by one rather than globbed: the first route matching a request wins, so
// the order they register in is a decision, and stating it here means adding a domain cannot
// silently change it the way alphabetical filenames would.
require __DIR__.'/guest.php';

// Applied here rather than in each domain file so authentication is the default: anything
// reachable without a session has to join the short list above, where it stays conspicuous.
Route::middleware(['auth', 'auth.session', IdleTimeout::class])->group(function () {
    require __DIR__.'/foundation.php';
    require __DIR__.'/sale.php';
});
