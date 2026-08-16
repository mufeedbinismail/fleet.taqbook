<?php

namespace App\Trade\Sale\Model;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'debtors_master';

    protected $primaryKey = 'debtor_no';

    public $timestamps = false;

    protected $fillable = [
        'debtor_no',
        'name',
        'debtor_ref',
        'address',
        'tax_id',
        'curr_code',
        'sales_type',
        'dimension_id',
        'dimension2_id',
        'credit_status',
        'payment_terms',
        'discount',
        'pymt_discount',
        'credit_limit',
        'notes',
        'inactive',
    ];
}
