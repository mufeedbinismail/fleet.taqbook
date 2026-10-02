<?php

namespace App\Fleet\Component\Table;

use App\Fleet\Model\Deployment;
use App\Foundation\Component\Select\Control\SelectControl;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Contract\TableDefinitionContract;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Enum\Stick;
use App\Foundation\Component\Table\Filter\DateRangeFilter;
use App\Foundation\Component\Table\Filter\ExactFilter;
use App\Foundation\Component\Table\Filter\TextFilter;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\Table;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trade\Sale\Component\Select\CustomerSelect;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Every client install we run, and who each one belongs to.
 */
final class DeploymentTable implements TableDefinitionContract
{
    public function __construct(
        private readonly OptionService $optionService,
        private readonly CustomerSelect $customerSelect,
    ) {}

    public static function routeName(): string
    {
        return 'fleet.deployments.list';
    }

    public function table(TableBuilder $table): Table
    {
        return $table
            ->of($this->rows())
            ->name('deployments')
            ->searchable('deployments.number', 'deployments.alias', 'debtors_master.name', 'deployments.url')
            ->defaultSort('deployments.number')
            ->perPage(25, max: 100)
            ->definitions(...$this->definitions())
            ->map($this->row(...))
            ->footer($this->count(...))
            ->definition();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Deployment $record): array
    {
        return [
            'uuid' => $record->uuid,
            'number' => $record->number,
            'alias' => $record->alias,
            'debtor_no' => $record->debtor_no,
            'customer_name' => $record->customer_name,
            'hosting_label' => $record->hosting->label(),
            'hosting' => $record->hosting->value,
            'status_label' => $record->status->label(),
            'status' => $record->status->value,
            'instance_created_date' => $record->instance_created_date->format(DomainDateTime::userDateFormat()),
            'url' => $record->url,
            'last_pushed_at' => $record->last_pushed_at?->format(DomainDateTime::userDateTimeFormat()),
            'identity' => $record->identity_issued_at === null ? null : __('fleet.deployment.identity', [
                'ver' => $record->identity_ver,
                'date' => $record->identity_issued_at->format(DomainDateTime::userDateFormat()),
            ]),
        ];
    }

    /**
     * How many installs the filters left, over the whole narrowed set rather than the page.
     *
     * @return list<array<string, mixed>>
     */
    private function count(EloquentBuilder|QueryBuilder $rows): array
    {
        return [['number' => __('fleet.deployment.footer.total', ['count' => $rows->count()])]];
    }

    /**
     * The customer is joined rather than loaded, because the table sorts and filters by its name
     * in the database and neither is expressible over a relation resolved per row.
     */
    private function rows(): EloquentBuilder
    {
        return Deployment::query()
            ->leftJoin('debtors_master', 'debtors_master.debtor_no', '=', 'deployments.debtor_no')
            ->select([
                'deployments.uuid',
                'deployments.number',
                'deployments.alias',
                'deployments.debtor_no',
                'deployments.hosting',
                'deployments.status',
                'deployments.url',
                'deployments.instance_created_date',
                'deployments.last_pushed_at',
                'deployments.identity_ver',
                'deployments.identity_issued_at',
                'debtors_master.name as customer_name',
            ]);
    }

    /**
     * @return list<ColumnDefinition>
     */
    private function definitions(): array
    {
        return [
            new ColumnDefinition(
                'number',
                __('fleet.deployment.column.number'),
                sortable: 'deployments.number',
                filter: new TextFilter('deployments.number'),
                width: '8rem',
                sticky: Stick::Start,
            ),
            new ColumnDefinition(
                'alias',
                __('fleet.deployment.column.alias'),
                sortable: 'deployments.alias',
                filter: new TextFilter('deployments.alias'),
                width: '12rem',
            ),
            new ColumnDefinition(
                'customer_name',
                __('fleet.deployment.column.customer'),
                sortable: 'debtors_master.name',
                filter: new ExactFilter(
                    'deployments.debtor_no',
                    SelectControl::from($this->optionService->channel($this->customerSelect))
                ),
                width: '16rem',
            ),
            new ColumnDefinition(
                'hosting_label',
                __('fleet.deployment.column.hosting'),
                sortable: 'deployments.hosting',
                width: '10rem'
            ),
            new ColumnDefinition(
                'status_label',
                __('fleet.deployment.column.status'),
                sortable: 'deployments.status',
                width: '9rem'
            ),
            new ColumnDefinition(
                'last_pushed_at',
                __('fleet.deployment.column.last_reached'),
                sortable: 'deployments.last_pushed_at',
                dataType: DataType::DateTime,
                width: '11rem',
            ),
            new ColumnDefinition(
                'identity',
                __('fleet.deployment.column.identity'),
                sortable: 'deployments.identity_ver',
                width: '10rem',
            ),
            new ColumnDefinition(
                'instance_created_date',
                __('fleet.deployment.column.instance_created'),
                sortable: 'deployments.instance_created_date',
                filter: new DateRangeFilter('deployments.instance_created_date'),
                dataType: DataType::Date,
                width: '10rem',
            ),
            new ColumnDefinition('url', __('fleet.deployment.column.address'), width: '18rem'),
        ];
    }
}
