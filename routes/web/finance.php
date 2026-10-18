<?php

use App\Finance\Ledger\Component\Select\LedgerAccountSelect;
use Illuminate\Support\Facades\Route;

Route::optionList('finance/accounts', LedgerAccountSelect::class);
