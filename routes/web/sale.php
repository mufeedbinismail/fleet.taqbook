<?php

use App\Foundation\Auth\Constant\Permission;
use App\Trade\Sale\Component\Select\CustomerSelect;
use App\Trade\Sale\Component\Select\SalesTypeSelect;
use App\Trade\Sale\Http\Controller\ItemSalePriceController;
use Illuminate\Support\Facades\Route;

Route::optionList('sale/customers', CustomerSelect::class)->middleware('can:'.Permission::AUTHENTICATED);
Route::optionList('sale/sales-types', SalesTypeSelect::class)->middleware('can:'.Permission::MANAGE_SALE_PRICE);

Route::prefix('sales/prices')
    ->name('sale.prices.')
    ->middleware('can:'.Permission::MANAGE_SALE_PRICE)
    ->group(function () {
        Route::get('/items/{item}', [ItemSalePriceController::class, 'index'])
            ->name('items.index');
    });
