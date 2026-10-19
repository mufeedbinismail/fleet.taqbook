<?php

namespace App\Inventory\Provider;

use App\Foundation\Navigation\Facade\Navigation;
use App\Inventory\Source\ItemSource;
use App\Inventory\View\ItemEditorComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Navigation::register(ItemSource::class);

        View::composer('components.inventory.item-editor', ItemEditorComposer::class);
    }
}
