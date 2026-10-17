<?php

use App\Foundation\Auth\Constant\Permission;
use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Component\Select\ItemSelect;
use App\Inventory\Component\Table\ItemTable;
use App\Inventory\Http\Controller\ItemController;
use Illuminate\Support\Facades\Route;

Route::tableData('inventory/items', ItemTable::class)->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM);

Route::optionList('inventory/items', ItemSelect::class);
Route::optionList('inventory/categories', ItemCategorySelect::class);

Route::prefix('inventory/items')
    ->name('inventory.items.')
    ->group(function () {
        Route::get('/', [ItemController::class, 'index'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('index');

        Route::get('/{item}', [ItemController::class, 'show'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('show');

        Route::get('/{item}/status', [ItemController::class, 'status'])
            ->middleware('can:'.Permission::VIEW_INVENTORY_STATUS)
            ->name('status');
    });
