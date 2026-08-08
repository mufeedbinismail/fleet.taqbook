<?php

namespace App\Foundation\Shared\Model;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'sys_prefs';
    protected $primaryKey = 'name';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'name',
        'category',
        'type',
        'length',
        'value',
    ];
}

