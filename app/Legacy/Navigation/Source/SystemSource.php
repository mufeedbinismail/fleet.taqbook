<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Constant\Permission;
use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Builder\SectionBuilder;
use App\Navigation\Constant\Area;
use App\Navigation\Constant\Section;
use App\Navigation\Enum\Category;
use App\Navigation\Enum\Column;

/**
 * The Setup area, plus every legacy entry that currently lives in it.
 *
 * Several of these are keyed to other domains — taxes and GL setup, points of sale — and sit here
 * only while those domains are unported. The sections are Setup's own and outlive them.
 */
class SystemSource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::SYSTEM, $this->label('S&etup'), function (AreaBuilder $system) {
            $system->icon('icon-settings')
                ->help('Setup')
                ->sort(80)
                ->target($this->script('admin/dashboard.php', ['sel_app' => 'system']));

            $system->section(Section::SYSTEM_COMPANY, $this->label('Company Setup'), function (SectionBuilder $section) {
                $section->page('preference.manage', $this->label('&Company Setup'))
                    ->target($this->script('admin/company_preferences.php'))
                    ->permission(Permission::CONFIGURE_COMPANY)
                    ->category(Category::Settings)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('user.manage', $this->label('&User Accounts Setup'))
                    ->target($this->script('admin/users.php'))
                    ->permission(Permission::MANAGE_USER)
                    ->category(Category::Settings)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('role.manage', $this->label('&Access Setup'))
                    ->target($this->script('admin/security_roles.php'))
                    ->permission(Permission::MANAGE_ROLE)
                    ->category(Category::Settings)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('display.manage', $this->label('&Display Setup'))
                    ->target($this->script('admin/display_prefs.php'))
                    ->permission(Permission::CONFIGURE_DISPLAY)
                    ->category(Category::Settings)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('form-template.manage', $this->label('Transaction &References'))
                    ->target($this->script('admin/forms_setup.php'))
                    ->permission(Permission::MANAGE_FORM_TEMPLATE)
                    ->category(Category::Settings)
                    ->place(Column::Left)
                    ->sort(50);

                $section->page('tax-rate.manage', $this->label('&Taxes'))
                    ->target($this->script('taxes/tax_types.php'))
                    ->permission(Permission::MANAGE_TAX_RATE)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(60);

                $section->page('tax-group.manage', $this->label('Tax &Groups'))
                    ->target($this->script('taxes/tax_groups.php'))
                    ->permission(Permission::MANAGE_TAX_GROUP)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(70);

                $section->page('item-tax-type.manage', $this->label('Item Ta&x Types'))
                    ->target($this->script('taxes/item_tax_types.php'))
                    ->permission(Permission::MANAGE_ITEM_TAX_TYPE)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(80);

                $section->page('ledger-setup.manage', $this->label('System and &General GL Setup'))
                    ->target($this->script('admin/gl_setup.php'))
                    ->permission(Permission::CONFIGURE_LEDGER)
                    ->category(Category::Settings)
                    ->place(Column::Right)
                    ->sort(90);

                $section->page('fiscal-year.manage', $this->label('&Fiscal Years'))
                    ->target($this->script('admin/fiscalyears.php'))
                    ->permission(Permission::MANAGE_FISCAL_YEAR)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(100);

                $section->page('print-profile.manage', $this->label('&Print Profiles'))
                    ->target($this->script('admin/print_profiles.php'))
                    ->permission(Permission::MANAGE_PRINT_PROFILE)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(110);
            })->sort(10);

            $system->section(Section::SYSTEM_MISCELLANEOUS, $this->label('Miscellaneous'), function (SectionBuilder $section) {
                $section->page('payment-term.manage', $this->label('Pa&yment Terms'))
                    ->target($this->script('admin/payment_terms.php'))
                    ->permission(Permission::MANAGE_PAYMENT_TERM)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('shipping.manage', $this->label('Shi&pping Company'))
                    ->target($this->script('admin/shipping_companies.php'))
                    ->permission(Permission::MANAGE_SHIPPING)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('pos.manage', $this->label('&Points of Sale'))
                    ->target($this->script('sales/manage/sales_points.php'))
                    ->permission(Permission::MANAGE_POS)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(30);

                $section->page('printer.manage', $this->label('&Printers'))
                    ->target($this->script('admin/printers.php'))
                    ->permission(Permission::MANAGE_PRINTER)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(40);

                $section->page('contact-category.manage', $this->label('Contact &Categories'))
                    ->target($this->script('admin/crm_categories.php'))
                    ->permission(Permission::MANAGE_CONTACT_CATEGORY)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(50);
            })->sort(20);

            $system->section(Section::SYSTEM_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('transaction.void', $this->label('&Void a Transaction'))
                    ->target($this->script('admin/void_transaction.php'))
                    ->permission(Permission::VOID_RECORD)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('transaction.print', $this->label('View or &Print Transactions'))
                    ->target($this->script('admin/view_print_transaction.php'))
                    ->permission(Permission::VIEW_RECORD)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('attachment.manage', $this->label('&Attach Documents'))
                    ->target($this->script('admin/attachments.php', ['filterType' => '20']))
                    ->permission(Permission::MANAGE_ATTACHMENT)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('backup.manage', $this->label('&Backup'))
                    ->target($this->script('admin/backups.php'))
                    ->permission(Permission::MANAGE_BACKUP)
                    ->category(Category::System)
                    ->place(Column::Right)
                    ->sort(40);
            })->sort(30);

            $this->unlisted($system);
        });
    }

    /**
     * Reachable from the page chrome on every screen rather than from a menu, which is why it hangs
     * off the area: there is no entry above it to descend from.
     *
     * It asks for no permission. Anyone with a session may change their own password, so gating it
     * would only ever lock somebody out of themselves.
     */
    private function unlisted(AreaBuilder $system): void
    {
        $system->hiddenPage('password.change', $this->label('Change password'))
            ->target($this->script('admin/change_current_user_password.php'));
    }
}
