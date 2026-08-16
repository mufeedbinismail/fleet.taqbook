<?php

namespace App\Trade\Sale\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerBranch extends Model
{
    protected $table = 'cust_branch';

    protected $primaryKey = 'branch_code';

    public $timestamps = false;

    protected $fillable = [
        'branch_code',
        'debtor_no',
        'br_name',
        'branch_ref',
        'br_address',
        'area',
        'salesman',
        'default_location',
        'tax_group_id',
        'sales_account',
        'sales_discount_account',
        'receivables_account',
        'payment_discount_account',
        'default_ship_via',
        'br_post_address',
        'group_no',
        'notes',
        'bank_account',
        'inactive',
    ];
}
