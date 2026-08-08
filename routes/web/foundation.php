<?php

use App\Foundation\Auth\Http\Controller\AuthenticationController;
use App\Foundation\Navigation\Http\Controller\AreaIndexController;
use Illuminate\Support\Facades\Route;

Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');

Route::get('area/{key}', AreaIndexController::class)
    ->where('key', '[A-Za-z0-9._-]+')
    ->name('navigation.area-index');
