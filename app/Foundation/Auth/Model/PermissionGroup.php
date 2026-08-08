<?php

namespace App\Foundation\Auth\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    protected $fillable = [
        'key',
        'name',
        'sort',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class)->orderBy('sort');
    }
}
