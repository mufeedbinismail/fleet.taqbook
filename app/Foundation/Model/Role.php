<?php

namespace App\Foundation\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'security_roles';
    public $timestamps = false;

    /** @var array<string, true>|null */
    private ?array $permissionKeys = null;

    protected $fillable = [
        'role',
        'description',
        'inactive',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function hasPermission(string $key): bool
    {
        return isset($this->permissionKeys()[$key]);
    }

    public function hasConfiguredAccess(): bool
    {
        return $this->permissionKeys() !== [];
    }

    /**
     * Memoized for the request, mirroring FA's login-time caching: a role edited mid-session
     * is not reflected until the next request.
     *
     * @return array<string, true>
     */
    private function permissionKeys(): array
    {
        return $this->permissionKeys ??= $this->permissions()->pluck('key')->flip()->all();
    }
}
