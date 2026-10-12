<?php

use App\Inventory\Component\Select\ItemCategorySelect;

Route::optionList('inventory/categories', ItemCategorySelect::class);
