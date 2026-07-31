<?php

namespace App\Legacy\Navigation\Source;

use App\Foundation\Constant\Permission;
use App\Legacy\Navigation\Condition\DimensionsEnabled;
use App\Legacy\Navigation\Enum\Query;
use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Builder\SectionBuilder;
use App\Navigation\Constant\Area;
use App\Navigation\Constant\Section;
use App\Navigation\Enum\Category;
use App\Navigation\Enum\Column;

/**
 * Dimensions are placed as a run of their own wherever they appear, rather than continuing the run
 * above them. They are switched on and off as a set, so keeping them apart means a company that
 * does not use them is left with exactly the runs it would have had, and one that does gets them
 * read as the group they are.
 */
class FinanceSource extends LegacySource
{
    public function declare(Builder $nav): void
    {
        $nav->area(Area::FINANCE, $this->label('&Banking and General Ledger'), function (AreaBuilder $finance) {
            $finance->icon('icon-accountant')
                ->help('Banking and General Ledger')
                ->sort(70)
                ->target($this->script('admin/dashboard.php', ['sel_app' => 'GL']));

            $finance->section(Section::FINANCE_TRANSACTION, $this->label('Transactions'), function (SectionBuilder $section) {
                $section->page('payment.create', $this->label('&Payments'))
                    ->target($this->script('gl/gl_bank.php', ['NewPayment' => 'Yes']))
                    ->permission(Permission::CREATE_BANKING_PAYMENT)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('deposit.create', $this->label('&Deposits'))
                    ->target($this->script('gl/gl_bank.php', ['NewDeposit' => 'Yes']))
                    ->permission(Permission::CREATE_DEPOSIT)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('transfer.create', $this->label('Bank Account &Transfers'))
                    ->target($this->script('gl/bank_transfer.php'))
                    ->permission(Permission::CREATE_BANKING_TRANSFER)
                    ->category(Category::Transaction)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('journal-entry.create', $this->label('&Journal Entry'))
                    ->target($this->script('gl/gl_journal.php', ['NewJournal' => 'Yes']))
                    ->permission(Permission::CREATE_LEDGER_JOURNAL_ENTRY)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(40);

                $section->page('budget.manage', $this->label('&Budget Entry'))
                    ->target($this->script('gl/gl_budget.php'))
                    ->permission(Permission::MANAGE_BUDGET)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(50);

                $section->page('reconciliation.create', $this->label('&Reconcile Bank Account'))
                    ->target($this->script('gl/bank_account_reconcile.php'))
                    ->permission(Permission::CREATE_RECONCILIATION)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(60);

                $section->page('accrual.create', $this->label('Revenue / &Costs Accruals'))
                    ->target($this->script('gl/accruals.php'))
                    ->permission(Permission::CREATE_ACCRUAL)
                    ->category(Category::Transaction)
                    ->place(Column::Right)
                    ->sort(70);

                $section->page('dimension.create', $this->label('Dimension &Entry'))
                    ->target($this->script('dimensions/dimension_entry.php'))
                    ->permission(Permission::CREATE_DIMENSION)
                    ->category(Category::Entry)
                    ->when(DimensionsEnabled::class)
                    ->place(Column::Left, cluster: 1)
                    ->sort(80);
            })->sort(10);

            $finance->section(Section::FINANCE_INQUIRY, $this->label('Inquiries and Reports'), function (SectionBuilder $section) {
                $section->page('journal-entry.inquire', $this->label('&Journal Inquiry'))
                    ->target($this->script('gl/inquiry/journal_inquiry.php'))
                    ->permission(Permission::LEDGER_POSTING_ANALYTICS)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('posting.inquire', $this->label('GL &Inquiry'))
                    ->target($this->script('gl/inquiry/gl_account_inquiry.php'))
                    ->permission(Permission::VIEW_LEDGER_POSTING)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('bank-account.inquire', $this->label('Bank Account &Inquiry'))
                    ->target($this->script('gl/inquiry/bank_inquiry.php'))
                    ->permission(Permission::VIEW_BANKING_TRANSACTION)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('tax.inquire', $this->label('Ta&x Inquiry'))
                    ->target($this->script('gl/inquiry/tax_inquiry.php'))
                    ->permission(Permission::TAX_TRANSACTION_REPORT)
                    ->category(Category::Inquiry)
                    ->place(Column::Left)
                    ->sort(40);

                $section->page('trial-balance.inquire', $this->label('Trial &Balance'))
                    ->target($this->script('gl/inquiry/gl_trial_balance.php'))
                    ->permission(Permission::LEDGER_POSTING_ANALYTICS)
                    ->category(Category::Inquiry)
                    ->place(Column::Right)
                    ->sort(50);

                $section->page('balance-sheet.inquire', $this->label('Balance &Sheet Drilldown'))
                    ->target($this->script('gl/inquiry/balance_sheet.php'))
                    ->permission(Permission::LEDGER_POSTING_ANALYTICS)
                    ->category(Category::Inquiry)
                    ->place(Column::Right)
                    ->sort(60);

                $section->page('profit-loss.inquire', $this->label('&Profit and Loss Drilldown'))
                    ->target($this->script('gl/inquiry/profit_loss.php'))
                    ->permission(Permission::LEDGER_POSTING_ANALYTICS)
                    ->category(Category::Inquiry)
                    ->place(Column::Right)
                    ->sort(70);

                $section->page('banking.report', $this->label('Banking &Reports'))
                    ->target($this->reports(5))
                    ->permission(Permission::BANKING_TRANSACTION_REPORT)
                    ->category(Category::Report)
                    ->place(Column::Right)
                    ->sort(80);

                $section->page('posting.report', $this->label('General Ledger &Reports'))
                    ->target($this->reports(6))
                    ->permission(Permission::LEDGER_POSTING_REPORT)
                    ->category(Category::Report)
                    ->place(Column::Right)
                    ->sort(90);

                $section->page('dimension.outstanding', $this->label('&Outstanding Dimensions'))
                    ->target($this->script('dimensions/inquiry/search_dimensions.php', ['outstanding_only' => '1']))
                    ->permission(Permission::VIEW_DIMENSION)
                    ->category(Category::Transaction)
                    ->when(DimensionsEnabled::class)
                    ->place(Column::Left, cluster: 1)
                    ->sort(100);

                $section->page('dimension.inquire', $this->label('Dimension &Inquiry'))
                    ->target($this->script('dimensions/inquiry/search_dimensions.php'))
                    ->permission(Permission::VIEW_DIMENSION)
                    ->category(Category::Inquiry)
                    ->when(DimensionsEnabled::class)
                    ->place(Column::Left, cluster: 1)
                    ->sort(110);

                $section->page('dimension.report', $this->label('Dimension &Reports'))
                    ->target($this->reports(4))
                    ->permission(Permission::DIMENSION_REPORT)
                    ->category(Category::Report)
                    ->when(DimensionsEnabled::class)
                    ->place(Column::Right, cluster: 1)
                    ->sort(120);
            })->sort(20);

            $finance->section(Section::FINANCE_MAINTENANCE, $this->label('Maintenance'), function (SectionBuilder $section) {
                $section->page('bank-account.manage', $this->label('Bank &Accounts'))
                    ->target($this->script('gl/manage/bank_accounts.php'))
                    ->permission(Permission::MANAGE_BANKING_ACCOUNT)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(10);

                $section->page('quick-entry.manage', $this->label('&Quick Entries'))
                    ->target($this->script('gl/manage/gl_quick_entries.php'))
                    ->permission(Permission::MANAGE_QUICK_ENTRY)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(20);

                $section->page('account-tag.manage', $this->label('Account &Tags'))
                    ->target($this->script('admin/tags.php', ['type' => 'account']))
                    ->permission(Permission::MANAGE_ACCOUNT_TAG)
                    ->category(Category::Maintenance)
                    ->place(Column::Left)
                    ->sort(30);

                $section->page('currency.manage', $this->label('&Currencies'))
                    ->target($this->script('gl/manage/currencies.php'))
                    ->permission(Permission::MANAGE_CURRENCY)
                    ->category(Category::Maintenance)
                    ->place(Column::Left, cluster: 1)
                    ->sort(40);

                $section->page('exchange-rate.manage', $this->label('&Exchange Rates'))
                    ->target($this->script('gl/manage/exchange_rates.php'))
                    ->permission(Permission::MANAGE_EXCHANGE_RATE)
                    ->category(Category::Maintenance)
                    ->place(Column::Left, cluster: 1)
                    ->sort(50);

                $section->page('account.manage', $this->label('&GL Accounts'))
                    ->target($this->script('gl/manage/gl_accounts.php'))
                    ->permission(Permission::MANAGE_LEDGER_ACCOUNT)
                    ->category(Category::Entry)
                    ->place(Column::Right)
                    ->sort(60);

                $section->page('account-group.manage', $this->label('GL Account &Groups'))
                    ->target($this->script('gl/manage/gl_account_types.php'))
                    ->permission(Permission::MANAGE_ACCOUNT_GROUP)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(70);

                $section->page('account-class.manage', $this->label('GL Account &Classes'))
                    ->target($this->script('gl/manage/gl_account_classes.php'))
                    ->permission(Permission::MANAGE_ACCOUNT_CLASS)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(80);

                $section->page('period.close', $this->label('&Closing GL Transactions'))
                    ->target($this->script('gl/manage/close_period.php'))
                    ->permission(Permission::CLOSE_PERIOD)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(90);

                $section->page('revaluation.process', $this->label('&Revaluation of Currency Accounts'))
                    ->target($this->script('gl/manage/revaluate_currencies.php'))
                    ->permission(Permission::MANAGE_EXCHANGE_RATE)
                    ->category(Category::Maintenance)
                    ->place(Column::Right)
                    ->sort(100);

                $section->page('dimension-tag.manage', $this->label('Dimension &Tags'))
                    ->target($this->script('admin/tags.php', ['type' => 'dimension']))
                    ->permission(Permission::MANAGE_DIMENSION_TAG)
                    ->category(Category::Maintenance)
                    ->when(DimensionsEnabled::class)
                    ->place(Column::Left, cluster: 2)
                    ->sort(110);
            })->sort(30);

            $this->unlisted($finance);
        });
    }

    /**
     * Places a menu never offers, reached by acting on a record.
     *
     * The viewers and the edit screens hang off the area rather than off an entry: they are reached
     * by transaction type from wherever a reference is printed, so no single entry above them is
     * the one a reader came through.
     *
     * The account drill-down is the exception, and hangs where it is genuinely reached from.
     */
    private function unlisted(AreaBuilder $finance): void
    {
        $finance->hiddenPage('transfer.view', $this->label('View Bank Transfer'))
            ->target($this->script('gl/view/bank_transfer_view.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_BANKING_TRANSACTION);

        $finance->hiddenPage('payment.view', $this->label('View Bank Payment'))
            ->target($this->script('gl/view/gl_payment_view.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_BANKING_TRANSACTION);

        $finance->hiddenPage('deposit.view', $this->label('View Bank Deposit'))
            ->target($this->script('gl/view/gl_deposit_view.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_BANKING_TRANSACTION);

        $finance->hiddenPage('posting.view', $this->label('General Ledger Transaction Details'))
            ->target($this->script('gl/view/gl_trans_view.php', ['trans_no' => Query::ANY, 'type_id' => Query::ANY]))
            ->permission(Permission::VIEW_LEDGER_POSTING);

        $finance->hiddenPage('dimension.view', $this->label('View Dimension'))
            ->target($this->script('dimensions/view/view_dimension.php', ['trans_no' => Query::ANY]))
            ->permission(Permission::VIEW_DIMENSION)
            ->when(DimensionsEnabled::class);

        $finance->hiddenPage('journal-entry.modify', $this->label('Modifying Journal Transaction'))
            ->target($this->script('gl/gl_journal.php', ['ModifyGL' => 'Yes', 'trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_LEDGER_JOURNAL_ENTRY);

        $finance->hiddenPage('payment.modify', $this->label('Modify Bank Account Entry'))
            ->target($this->script('gl/gl_bank.php', ['ModifyPayment' => 'Yes', 'trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_BANKING_PAYMENT);

        $finance->hiddenPage('deposit.modify', $this->label('Modify Bank Deposit Entry'))
            ->target($this->script('gl/gl_bank.php', ['ModifyDeposit' => 'Yes', 'trans_no' => Query::ANY]))
            ->permission(Permission::CREATE_DEPOSIT);

        $finance->hiddenPage('transfer.modify', $this->label('Modify Bank Account Transfer'))
            ->under('finance.transfer.create')
            ->target($this->script('gl/bank_transfer.php', ['ModifyTransfer' => Query::ANY]))
            ->permission(Permission::CREATE_BANKING_TRANSFER);

        $finance->hiddenPage('accrual.inspect', $this->label('Search General Ledger Transactions'))
            ->under('finance.accrual.create')
            ->target($this->script('gl/view/accrual_trans.php', ['act' => Query::ANY]))
            ->permission(Permission::VIEW_LEDGER_POSTING);
    }
}
