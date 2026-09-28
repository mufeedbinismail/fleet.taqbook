<?php

use App\Foundation\Component\Shared\Http\Controller\ComponentGalleryController;
use Illuminate\Support\Facades\Route;

Route::get('demo/component-gallery', ComponentGalleryController::class)->name('demo.component-gallery');
