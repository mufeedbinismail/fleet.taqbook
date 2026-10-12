<?php

use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Component\Select\ItemSelect;
use App\Inventory\Component\Table\ItemTable;

Route::tableData('inventory/items', ItemTable::class)->middleware('can:'.Permission::MANAGE_INVENTORY_ITEM);

Route::optionList('inventory/items', ItemSelect::class);
Route::optionList('inventory/categories', ItemCategorySelect::class);
