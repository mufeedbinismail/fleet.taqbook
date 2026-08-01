<?php

use App\Foundation\Http\Controller\AuthenticationController;
use App\Navigation\Http\Controller\AreaIndexController;
use App\Foundation\Http\Middleware\IdleTimeout;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthenticationController::class, 'show'])->name('login');
Route::post('login', [AuthenticationController::class, 'login']);
Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'auth.session', IdleTimeout::class])->group(function () {
    Route::get('area/{key}', AreaIndexController::class)
        ->where('key', '[A-Za-z0-9._-]+')
        ->name('navigation.area-index');
});
