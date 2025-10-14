<?php

use Illuminate\Support\Facades\Route;

// Catch-all route for legacy URLs
Route::match(
    ['get', 'post'],
    '{legacyRoute}',
    \App\Http\Controllers\LegacyRequestController::class
)->where('legacyRoute', '.*')->fallback();