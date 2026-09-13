<?php

namespace App\Foundation\Auth\Component\Select;

use App\Foundation\Auth\Model\Role;
use App\Foundation\Component\Select\Contract\NarrowsOptions;
use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Every role as a pick list, inactive ones included and marked: a list that hid them could not
 * find the accounts still holding one. `inactive=0` is the one narrowing, for a list that offers
 * roles to be newly given rather than looked up.
 */
final class RoleSelect implements NarrowsOptions, SelectDefinition
{
    public const INACTIVE = 'inactive';

    public function query(): EloquentBuilder
    {
        return Role::query()
            ->select(['security_roles.id as value', 'security_roles.role as label'])
            ->selectRaw('CASE WHEN security_roles.inactive THEN ? END as description', [__('foundation.role.picker.inactive')])
            ->orderBy('security_roles.role');
    }

    public function filterRules(): array
    {
        return [self::INACTIVE => ['nullable', 'boolean']];
    }

    public function applyFilters(EloquentBuilder|QueryBuilder $query, SelectState $state): void
    {
        $asked = $state->filter(self::INACTIVE);

        // Absent leaves the list wide: an unasked question is not an answer of "hide them".
        if ($asked !== null && filter_var($asked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false) {
            $query->where('security_roles.inactive', 0);
        }
    }

    public function searchColumns(): array
    {
        return ['security_roles.role'];
    }

    public function valueColumn(): string
    {
        return 'security_roles.id';
    }

    public static function routeName(): string
    {
        return 'access.roles.options';
    }
}
