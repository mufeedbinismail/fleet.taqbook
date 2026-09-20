<?php

namespace App\Fleet\Source;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Navigation\Builder\AreaBuilder;
use App\Foundation\Navigation\Builder\NavigationBuilder;
use App\Foundation\Navigation\Builder\SectionBuilder;
use App\Foundation\Navigation\Constant\Area;
use App\Foundation\Navigation\Constant\Section;
use App\Foundation\Navigation\Contract\NavigationSourceContract;
use App\Foundation\Navigation\Enum\Category;
use App\Foundation\Navigation\Enum\Column;
use App\Foundation\Navigation\Target\RouteTarget;

/**
 * Every client install we run, and the screens that keep the record of them.
 */
class FleetSource implements NavigationSourceContract
{
    public function declare(NavigationBuilder $nav): void
    {
        $nav->area(Area::FLEET, 'fleet.area.title', function (AreaBuilder $fleet) {
            $fleet->icon('icon-fleet')
                ->help('fleet.area.help')
                ->sort(90)
                ->permission(Permission::MANAGE_DEPLOYMENT)
                ->target(RouteTarget::to('navigation.area-index', ['key' => Area::FLEET]));

            $fleet->section(Section::FLEET_MAINTENANCE, 'fleet.section.maintenance', function (SectionBuilder $section) {
                $section->page('deployment.manage', 'fleet.deployment.title')
                    ->icon('icon-server')
                    ->target(RouteTarget::to('fleet.deployments.index'))
                    ->permission(Permission::MANAGE_DEPLOYMENT)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(10);
            });
        });
    }
}
