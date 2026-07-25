<?php

use App\Foundation\Http\Controller\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthenticationController::class, 'show'])->name('login');
Route::post('login', [AuthenticationController::class, 'login']);
Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
