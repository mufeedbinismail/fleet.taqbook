<?php

use App\Fleet\Component\Table\DeploymentTable;
use App\Fleet\Http\Controller\DeploymentController;
use App\Foundation\Auth\Constant\Permission;
use Illuminate\Support\Facades\Route;

Route::prefix('fleet/deployments')
    ->middleware('can:'.Permission::MANAGE_DEPLOYMENT)
    ->name('fleet.deployments.')
    ->group(function () {
        Route::get('/', [DeploymentController::class, 'index'])->name('index');
        Route::post('/', [DeploymentController::class, 'store'])->name('store');

        Route::put('{deployment}', [DeploymentController::class, 'update'])->name('update');
        Route::put('{deployment}/alias', [DeploymentController::class, 'rename'])->name('rename');
        Route::put('{deployment}/status', [DeploymentController::class, 'changeStatus'])->name('status');
        Route::post('{deployment}/ping', [DeploymentController::class, 'ping'])->name('ping');
        Route::post('{deployment}/identity', [DeploymentController::class, 'issueIdentity'])->name('identity');
        Route::delete('{deployment}', [DeploymentController::class, 'destroy'])->name('destroy');
        Route::delete('{deployment}/permanent', [DeploymentController::class, 'erase'])->name('erase');
    });

// Outside the register's own gate: entering is granted on its own, with or without keeping the register.
Route::post('fleet/deployments/{deployment}/support', [DeploymentController::class, 'enterAsSupport'])
    ->middleware('can:'.Permission::SUPPORT_DEPLOYMENT)
    ->name('fleet.deployments.support');

Route::tableData('fleet/deployments', DeploymentTable::class)
    ->middleware('can:'.Permission::MANAGE_DEPLOYMENT);
