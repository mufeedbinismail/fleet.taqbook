<?php

use App\Foundation\Auth\Constant\Permission;
use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Component\Select\ItemSelect;
use App\Inventory\Component\Table\ItemTable;
use App\Inventory\Http\Controller\ItemCategoryController;
use App\Inventory\Http\Controller\ItemController;
use Illuminate\Support\Facades\Route;

Route::tableData('inventory/items', ItemTable::class)->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM);

Route::optionList('inventory/items', ItemSelect::class);
Route::optionList('inventory/categories', ItemCategorySelect::class);

Route::prefix('inventory/categories')
    ->name('inventory.categories.')
    ->group(function () {
        Route::get('/{category}', [ItemCategoryController::class, 'show'])
            ->whereNumber('category')
            ->name('show');
    });

Route::prefix('inventory/items')
    ->name('inventory.items.')
    ->group(function () {
        Route::get('/', [ItemController::class, 'index'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('index');

        Route::post('/', [ItemController::class, 'store'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('store');

        Route::get('/{item}', [ItemController::class, 'show'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('show');

        Route::put('/{item}', [ItemController::class, 'update'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('update');

        Route::delete('/{item}', [ItemController::class, 'destroy'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('destroy');

        Route::put('/{item}/activation', [ItemController::class, 'setActivation'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('activation');

        Route::get('/{item}/image', [ItemController::class, 'image'])
            ->name('image');

        Route::delete('/{item}/image', [ItemController::class, 'destroyImage'])
            ->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM)
            ->name('image.destroy');

        Route::get('/{item}/status', [ItemController::class, 'status'])
            ->middleware('can:'.Permission::VIEW_INVENTORY_STATUS)
            ->name('status');
    });
