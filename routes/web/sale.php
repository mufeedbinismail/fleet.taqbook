<?php

use App\Foundation\Auth\Constant\Permission;
use App\Trade\Sale\Component\Select\CustomerSelect;
use Illuminate\Support\Facades\Route;

Route::optionList('sale/customers', CustomerSelect::class)->middleware('can:'.Permission::AUTHENTICATED);
