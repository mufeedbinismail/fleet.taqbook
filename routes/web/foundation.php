<?php

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Http\Controller\AuthenticationController;
use App\Foundation\Auth\Http\Controller\RoleController;
use App\Foundation\Auth\Http\Controller\UserPreferenceController;
use App\Foundation\Navigation\Http\Controller\AreaIndexController;
use Illuminate\Support\Facades\Route;

Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');

Route::post('preference/skin', [UserPreferenceController::class, 'skin'])->name('preference.skin');

Route::get('area/{key}', AreaIndexController::class)
    ->where('key', '[A-Za-z0-9._-]+')
    ->name('navigation.area-index');

Route::prefix('access/roles')
    ->middleware('can:'.Permission::MANAGE_ROLE)
    ->name('access.roles.')
    ->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('{role}', [RoleController::class, 'show'])->whereNumber('role')->name('show');
        Route::put('{role}', [RoleController::class, 'update'])->whereNumber('role')->name('update');
        Route::delete('{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('destroy');
    });
