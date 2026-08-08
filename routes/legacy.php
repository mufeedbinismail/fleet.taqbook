<?php

use App\Legacy\Http\Controller\Controller;
use Illuminate\Support\Facades\Route;

// Catch-all route for legacy URLs
Route::match(['get', 'post'], '{legacyRoute}', Controller::class)->where('legacyRoute', '.*')->fallback();