<?php

namespace App\Foundation\Auth\Source;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\Constant\Section;
use App\Foundation\Navigation\Contract\NavigationSource;
use App\Foundation\Navigation\Enum\Category;
use App\Foundation\Navigation\Enum\Column;
use App\Foundation\Navigation\ValueObject\RouteTarget;

/**
 * Who may do what, and the screens that decide it.
 *
 * The area and the section hosting these belong to another domain, so each entry is contributed
 * into a slot by name rather than nested inside one. That is what lets the key stay this domain's
 * own: an entry rehoused later keeps the name it is already known by.
 *
 * Sort is stated on every entry it competes with. Sharing a section across two sources leaves
 * declaration order decided by provider boot order, which no source can see, let alone control.
 */
class AccessSource implements NavigationSource
{
    public function declare(Builder $nav): void
    {
        $nav->page('foundation.access.role.manage', 'Access Setup', Section::SYSTEM_COMPANY)
            ->target(RouteTarget::to('access.roles.index'))
            ->permission(Permission::MANAGE_ROLE)
            ->category(Category::Settings)
            ->place(Column::Left)
            ->sort(30);
    }
}
