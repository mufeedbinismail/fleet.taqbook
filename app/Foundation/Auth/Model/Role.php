<?php

namespace App\Foundation\Auth\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'security_roles';

    public $timestamps = false;

    /** @var array<string, true>|null */
    private ?array $permissionKeys = null;

    protected $fillable = [
        'role',
        'inactive',
    ];

    protected $casts = [
        'reserved' => 'boolean',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_uuid');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_uuid');
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
