<?php

namespace App\Inventory\Model;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'stock_master';

    protected $primaryKey = 'stock_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'stock_id',
        'category_id',
        'tax_type_id',
        'description',
        'long_description',
        'units',
        'mb_flag',
        'sales_account',
        'cogs_account',
        'inventory_account',
        'adjustment_account',
        'wip_account',
        'dimension_id',
        'dimension2_id',
        'purchase_cost',
        'material_cost',
        'labour_cost',
        'overhead_cost',
        'inactive',
        'no_sale',
        'no_purchase',
        'editable',
        'depreciation_method',
        'depreciation_rate',
        'depreciation_factor',
        'depreciation_start',
        'depreciation_date',
        'fa_class_id',
    ];
}
