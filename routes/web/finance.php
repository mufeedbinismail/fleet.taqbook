<?php

use App\Finance\Ledger\Component\Select\LedgerAccountSelect;
use App\Finance\Shared\Component\Select\DimensionSelect;
use App\Finance\Tax\Component\Select\ItemTaxTypeSelect;
use Illuminate\Support\Facades\Route;

Route::optionList('finance/accounts', LedgerAccountSelect::class);
Route::optionList('finance/dimensions', DimensionSelect::class);
Route::optionList('finance/item-tax-types', ItemTaxTypeSelect::class);
