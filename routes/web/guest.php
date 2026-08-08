<?php

use App\Foundation\Auth\Http\Controller\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthenticationController::class, 'show'])->name('login');
Route::post('login', [AuthenticationController::class, 'login']);
