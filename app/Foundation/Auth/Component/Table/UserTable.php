<?php

namespace App\Foundation\Auth\Component\Table;

use App\Finance\Ledger\Query\TransactionAttributionExistsQuery;
use App\Foundation\Auth\Component\Select\RoleSelect;
use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Model\User;
use App\Foundation\Component\Select\Control\MultiSelectControl;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Contract\TableDefinition;
use App\Foundation\Component\Table\Enum\DataType;
use App\Foundation\Component\Table\Enum\Stick;
use App\Foundation\Component\Table\Filter\BooleanFilter;
use App\Foundation\Component\Table\Filter\DateRangeFilter;
use App\Foundation\Component\Table\Filter\InFilter;
use App\Foundation\Component\Table\Filter\TextFilter;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\FilterDefinition;
use App\Foundation\Component\Table\ValueObject\Table;
use App\Foundation\Component\Table\ValueObject\TableState;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Every login the system holds, and the access each of them works under.
 */
final class UserTable implements TableDefinition
{
    public function __construct(
        private readonly TransactionAttributionExistsQuery $attribution,
        private readonly OptionService $options,
        private readonly RoleSelect $roles,
        private readonly Guard $auth,
        private readonly Gate $gate,
    ) {}

    public static function routeName(): string
    {
        return 'access.users.list';
    }

    public function table(TableBuilder $tables): Table
    {
        return $tables
            ->of($this->rows())
            ->name('users')
            ->searchable('users.user_id', 'users.real_name', 'users.email', 'security_roles.role')
            // On the table rather than on a column: whether an account still works is asked of
            // the roster as a whole, not compared login by login.
            ->filterable(new FilterDefinition(
                'inactive',
                __('foundation.user.filter.inactive'),
                new BooleanFilter('users.inactive'),
            ))
            ->defaultSort('users.user_id')
            ->perPage(25, max: 100)
            ->definitions(...$this->definitions())
            ->map($this->row(...))
            ->footer($this->count(...))
            ->definition();
    }

    /**
     * A login the journal names is switched off and on, never deleted; one it does not name is
     * deleted, which is why an inactive login always has history. Nobody is offered what would lock
     * them out of this screen.
     *
     * @return array<string, mixed>
     */
    private function row(User $record): array
    {
        $own = (int) $record->id === (int) $this->auth->id();
        $history = (bool) $record->has_history;

        return [
            'id' => $record->id,
            'user_id' => $record->user_id,
            'real_name' => $record->real_name,
            'email' => $record->email,
            'phone' => $record->phone,
            'role_name' => $record->role_name,
            'last_visit' => $record->last_visit,
            'inactive' => $record->inactive,
            'role_id' => $record->role_id,
            'pos' => $record->pos,
            'is_editable' => ! $record->inactive,
            'is_switchable' => ! $own && $history,
            'is_deletable' => ! $own && ! $history,
        ];
    }

    /**
     * Where the roster opens: on the people who are still here, and only where the address named
     * nothing — a shared link opening somewhere other than where it was copied from is not a
     * default.
     */
    public function opensAt(TableState $asked): TableState
    {
        if (array_key_exists('inactive', $asked->filters)) {
            return $asked;
        }

        return new TableState(
            page: $asked->page,
            perPage: $asked->perPage,
            search: $asked->search,
            filters: [...$asked->filters, 'inactive' => false],
            sort: $asked->sort,
            export: $asked->export,
        );
    }

    /**
     * How many logins the filters left, over the whole narrowed set rather than the page: the only
     * count worth showing beneath a table somebody is paging through.
     *
     * @return list<array<string, mixed>>
     */
    private function count(EloquentBuilder|QueryBuilder $rows): array
    {
        return [['user_id' => __('foundation.user.footer.total', ['count' => $rows->count()])]];
    }

    /**
     * The role is joined rather than loaded, because a table sorts and filters by it in the database
     * and neither is expressible over a relation resolved per row.
     */
    private function rows(): EloquentBuilder
    {
        return User::query()
            ->when(
                $this->gate->denies(Permission::VIEW_RESERVED_ACCESS),
                fn (EloquentBuilder $query) => $query->where('users.reserved', false)
            )
            ->leftJoin('security_roles', 'security_roles.id', '=', 'users.role_id')
            ->select([
                'users.id',
                'users.user_id',
                'users.real_name',
                'users.email',
                'users.phone',
                // Empty rather than absent where the role has since been deleted, so the row still
                // draws and the account can be given a new one.
                DB::raw("COALESCE(security_roles.role, '') as role_name"),
                'users.last_visit_date as last_visit',
                'users.inactive',
                // Carried rather than drawn, so a row says what the account holds without being
                // read again.
                'users.role_id',
                'users.pos',
            ])
            // Appended rather than listed above, because select() replaces the list it is given.
            ->selectSub($this->attribution->builder(DB::raw('users.id')), 'has_history');
    }

    /**
     * @return list<ColumnDefinition>
     */
    private function definitions(): array
    {
        return [
            new ColumnDefinition(
                'user_id',
                __('foundation.user.column.login'),
                sortable: 'users.user_id',
                filter: new TextFilter('users.user_id'),
                width: '14rem',
                sticky: Stick::Start,
            ),
            new ColumnDefinition(
                'real_name',
                __('foundation.user.column.real_name'),
                sortable: 'users.real_name',
                filter: new TextFilter('users.real_name'),
                width: '14rem',
            ),
            new ColumnDefinition('phone', __('foundation.user.column.phone'), width: '10rem'),
            new ColumnDefinition('email', __('foundation.user.column.email'), sortable: 'users.email', filter: new TextFilter('users.email'), width: '16rem'),
            new ColumnDefinition(
                'last_visit',
                __('foundation.user.column.last_visit'),
                sortable: 'users.last_visit_date',
                filter: new DateRangeFilter('users.last_visit_date'),
                dataType: DataType::DateTime,
                width: '12rem',
            ),
            new ColumnDefinition(
                'role_name',
                __('foundation.user.column.role'),
                sortable: 'security_roles.role',
                // Narrowed by the id rather than by the name the column draws: two roles are free
                // to be renamed into each other's spelling, and neither is the one asked for.
                // Several at once, because who holds access of some weight is a question about a
                // set of roles rather than about any one of them.
                filter: new InFilter('users.role_id', MultiSelectControl::from($this->options->channel($this->roles))),
                width: '10rem',
            ),
            new ColumnDefinition('inactive', dataType: DataType::Boolean, visible: false, exportable: false),
            new ColumnDefinition('is_editable', dataType: DataType::Boolean, visible: false, exportable: false),
            new ColumnDefinition('is_switchable', dataType: DataType::Boolean, visible: false, exportable: false),
            new ColumnDefinition('is_deletable', dataType: DataType::Boolean, visible: false, exportable: false),
        ];
    }
}
