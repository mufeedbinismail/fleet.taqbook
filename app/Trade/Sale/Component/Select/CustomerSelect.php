<?php

namespace App\Trade\Sale\Component\Select;

use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Trade\Sale\Model\Customer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Every customer as a pick list, inactive ones included and marked: a deployment outlives the
 * trading relationship that opened it, and a list that hid them could not say whose an install
 * already on the register is.
 */
final class CustomerSelect implements SelectDefinition
{
    public function query(): EloquentBuilder
    {
        return Customer::query()
            ->select([
                'debtors_master.debtor_no as value',
                'debtors_master.name as label',
            ])
            ->selectRaw('CASE WHEN debtors_master.inactive THEN ? ELSE debtors_master.debtor_ref END as description', [
                __('trade.customer.picker.inactive'),
            ])
            ->orderBy('debtors_master.name');
    }

    public function searchColumns(): array
    {
        return ['debtors_master.name', 'debtors_master.debtor_ref'];
    }

    public function valueColumn(): string
    {
        return 'debtors_master.debtor_no';
    }

    public static function routeName(): string
    {
        return 'sale.customers.options';
    }
}
