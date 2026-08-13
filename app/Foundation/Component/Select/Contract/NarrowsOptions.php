<?php

namespace App\Foundation\Component\Select\Contract;

use App\Foundation\Component\Select\Intent\OptionSearchIntent;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * A definition whose list is narrowed by parameters of its own.
 *
 * Separate from the definition itself because most lists narrow by nothing, and one that declares
 * no rules should not have to say so — nor write an empty step for a narrowing it does not have.
 *
 * The two are stated together because neither half survives the other: a rule with no condition
 * reading it narrows nothing, and a condition reading a name no rule declared can never fire, the
 * intent carrying only what was named. Split across two places they would be free to disagree, and
 * a screen would find its narrowing silently doing nothing.
 */
interface NarrowsOptions
{
    /**
     * Rules for this select's own narrowing, keyed by parameter name. Whatever is named here — and
     * nothing else — reaches the intent as a filter.
     *
     * @return array<string, mixed>
     */
    public function filterRules(): array;

    /**
     * Narrow the rows by what the screen asked for.
     *
     * A step against the query rather than a query of its own, so that whatever it adds is in force
     * for both readings without either having to remember to ask for it.
     *
     * Whichever builder the definition's own query hands back, which an implementation cannot narrow
     * to the one it wants — so a step written here is confined to what both of them answer to.
     */
    public function applyFilters(EloquentBuilder|QueryBuilder $query, OptionSearchIntent $intent): void;
}
