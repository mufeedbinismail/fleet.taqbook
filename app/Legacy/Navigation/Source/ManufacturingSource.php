<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Constant\Permission;
use App\Legacy\Navigation\Condition\ManufacturingEnabled;
use App\Legacy\Navigation\Enum\Query;
use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Builder\SectionBuilder;
use App\Navigation\Constant\Area;
use App\Navigation\Constant\Section;
use App\Navigation\Enum\Category;
use App\Navigation\Enum\Column;

class ManufacturingSource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::MANUFACTURING, $this->label('&Manufacturing'), function (AreaBuilder $manufacturing) {
            $manufacturing->icon('icon-manufacturing')
                ->help('Manufacturing')
                ->sort(50)
                ->when(ManufacturingEnabled::class)
                ->target($this->script('admin/dashboard.php', ['sel_app' => 'manuf']));

            $manufacturing->section(Section::MANUFACTURING_TRANSACTION, $this->label('Transactions'), function (SectionBuilder $section) {
                $section->page('work-order.create', $this->label('Work &Order Entry'))
                    ->target($this->script('manufacturing/work_order_entry.php'))
                    ->permission(Permission::CREATE_WORK_ORDER)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('work-order.outstanding', $this->label('&Outstanding Work Orders'))
                    ->target($this->script('manufacturing/search_work_orders.php', ['outstanding_only' => '1']))
                    ->permission(Permission::VIEW_MANUFACTURING_OPERATION)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(20);
            })->sort(10);

            $manufacturing->section(Section::MANUFACTURING_INQUIRY, $this->label('Inquiries and Reports'), function (SectionBuilder $section) {
                $section->page('bom-cost.inquire', $this->label('Costed Bill Of Material Inquiry'))
                    ->target($this->script('manufacturing/inquiry/bom_cost_inquiry.php'))
                    ->permission(Permission::MANUFACTURING_COST_REPORT)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('where-used.inquire', $this->label('Inventory Item Where Used &Inquiry'))
                    ->target($this->script('manufacturing/inquiry/where_used_inquiry.php'))
                    ->permission(Permission::WORK_ORDER_ANALYTICS)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('work-order.inquire', $this->label('Work Order &Inquiry'))
                    ->target($this->script('manufacturing/search_work_orders.php'))
                    ->permission(Permission::VIEW_MANUFACTURING_OPERATION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('transaction.report', $this->label('Manufacturing &Reports'))
                    ->target($this->reports(3))
                    ->permission(Permission::VIEW_MANUFACTURING_OPERATION)
                    ->category(Category::Report)
                    ->place(Column::Right)
                    ->sort(40);
            })->sort(20);

            $manufacturing->section(Section::MANUFACTURING_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('bom.manage', $this->label('&Bills Of Material'))
                    ->target($this->script('manufacturing/manage/bom_edit.php'))
                    ->permission(Permission::MANAGE_BOM)
                    ->category(Category::Entry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('work-centre.manage', $this->label('&Work Centres'))
                    ->target($this->script('manufacturing/manage/work_centres.php'))
                    ->permission(Permission::MANAGE_WORK_CENTRE)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(20);
            })->sort(30);

            $this->unlisted($manufacturing);
        });
    }

    /**
     * Places a menu never offers.
     *
     * The viewers hang off the area, being reached by transaction type from wherever a reference is
     * printed. The four things done to a work order all open from the list of outstanding ones and
     * from nowhere else, which is the one subtree here where the entry above is not a choice.
     */
    private function unlisted(AreaBuilder $manufacturing): void
    {
        $manufacturing->hiddenPage('work-order.view', $this->label('View Work Order'))
            ->target($this->script('manufacturing/view/work_order_view.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_MANUFACTURING_OPERATION);

        $manufacturing->hiddenPage('work-order-cost.view', $this->label('View Work Order Costs'))
            ->target($this->script('manufacturing/view/wo_costs_view.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_MANUFACTURING_OPERATION);

        $manufacturing->hiddenPage('work-order-issue.view', $this->label('View Work Order Issue'))
            ->target($this->script('manufacturing/view/wo_issue_view.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_MANUFACTURING_OPERATION);

        $manufacturing->hiddenPage('work-order-production.view', $this->label('View Work Order Production'))
            ->target($this->script('manufacturing/view/wo_production_view.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_MANUFACTURING_OPERATION);

        $manufacturing->hiddenPage('work-order.release', $this->label('Work Order Release to Manufacturing'))
            ->under('inventory.manufacturing.work-order.outstanding')
            ->target($this->script('manufacturing/work_order_release.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_WORK_ORDER);

        $manufacturing->hiddenPage('work-order.issue', $this->label('Issue Items to Work Order'))
            ->under('inventory.manufacturing.work-order.outstanding')
            ->target($this->script('manufacturing/work_order_issue.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_WORK_ORDER);

        $manufacturing->hiddenPage('work-order.produce', $this->label('Produce or Unassemble Finished Items From Work Order'))
            ->under('inventory.manufacturing.work-order.outstanding')
            ->target($this->script('manufacturing/work_order_add_finished.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_WORK_ORDER);

        $manufacturing->hiddenPage('work-order.cost', $this->label('Work Order Additional Costs'))
            ->under('inventory.manufacturing.work-order.outstanding')
            ->target($this->script('manufacturing/work_order_costs.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_WORK_ORDER);
    }
}
