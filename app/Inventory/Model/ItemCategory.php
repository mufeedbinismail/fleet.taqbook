<?php

namespace App\Inventory\Model;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    protected $table = 'stock_category';

    protected $primaryKey = 'category_id';

    public $timestamps = false;
}
