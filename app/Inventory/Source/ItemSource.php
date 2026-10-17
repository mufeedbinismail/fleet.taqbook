<?php

namespace App\Inventory\Source;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Navigation\Builder\NavigationBuilder;
use App\Foundation\Navigation\Constant\Section;
use App\Foundation\Navigation\Contract\NavigationSourceContract;
use App\Foundation\Navigation\Enum\Category;
use App\Foundation\Navigation\Enum\Column;
use App\Foundation\Navigation\Target\RouteTarget;

/**
 * The items page, contributed into the Maintenance section.
 */
class ItemSource implements NavigationSourceContract
{
    public function declare(NavigationBuilder $nav): void
    {
        $nav->page('inventory.item.master', 'inventory.item.title', Section::INVENTORY_MAINTENANCE)
            ->target(RouteTarget::to('inventory.items.index'))
            ->permission(Permission::MANAGE_INVENTORY_ITEM)
            ->category(Category::Entry)
            ->place(Column::Left)
            ->sort(10);
    }
}
