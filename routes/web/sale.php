<?php

use App\Foundation\Auth\Constant\Permission;
use App\Trade\Sale\Component\Select\CustomerSelect;
use App\Trade\Sale\Component\Select\SalesTypeSelect;
use App\Trade\Sale\Http\Controller\ItemSalePriceController;
use App\Trade\Sale\Http\Controller\SalePriceController;
use Illuminate\Support\Facades\Route;

Route::optionList('sale/customers', CustomerSelect::class)->middleware('can:'.Permission::AUTHENTICATED);
Route::optionList('sale/sales-types', SalesTypeSelect::class)->middleware('can:'.Permission::MANAGE_SALE_PRICE);

Route::prefix('sales/prices')
    ->name('sale.prices.')
    ->middleware('can:'.Permission::MANAGE_SALE_PRICE)
    ->group(function () {
        Route::get('/items/{item}', [ItemSalePriceController::class, 'index'])
            ->name('items.index');

        Route::get('/items/{item}/suggestion', [SalePriceController::class, 'suggest'])
            ->name('items.suggestion');

        Route::post('/', [SalePriceController::class, 'store'])
            ->name('store');

        Route::put('/{price}', [SalePriceController::class, 'update'])
            ->whereNumber('price')
            ->name('update');

        Route::delete('/{price}', [SalePriceController::class, 'destroy'])
            ->whereNumber('price')
            ->name('destroy');
    });
