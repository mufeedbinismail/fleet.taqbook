<?php

use App\Foundation\Auth\Constant\Permission;
use App\Trade\Sale\Component\Select\CustomerSelect;
use App\Trade\Sale\Component\Select\SalesTypeSelect;
use Illuminate\Support\Facades\Route;

Route::optionList('sale/customers', CustomerSelect::class)->middleware('can:'.Permission::AUTHENTICATED);
Route::optionList('sale/sales-types', SalesTypeSelect::class)->middleware('can:'.Permission::MANAGE_SALE_PRICE);
