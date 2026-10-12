<?php

use App\Inventory\Component\Select\ItemCategorySelect;
use App\Inventory\Component\Select\ItemSelect;

Route::optionList('inventory/items', ItemSelect::class);
Route::optionList('inventory/categories', ItemCategorySelect::class);
