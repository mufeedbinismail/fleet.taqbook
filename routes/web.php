<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    require public_path('entry.php');
});