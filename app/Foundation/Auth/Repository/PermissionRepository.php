<?php

namespace App\Foundation\Auth\Repository;

use App\Foundation\Auth\Model\Permission;
use App\Foundation\Auth\Model\PermissionGroup;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PermissionRepository
{
    /**
     * Every grantable permission, grouped and in display order: a permission nobody may grant is
     * not offered.
     *
     * Handed out as the models themselves rather than as a read model: the catalog is only ever
     * rendered server-side, and nothing over the wire ever carries it.
     *
     * @return Collection<int, PermissionGroup>
     */
    public function catalog(): Collection
    {
        return PermissionGroup::with(['permissions' => fn (HasMany $permissions) => $permissions->where('reserved', false)])
            ->orderBy('sort')
            ->get();
    }

    /**
     * @param  list<string>  $keys
     */
    public function isAnyReserved(array $keys): bool
    {
        return $keys !== [] && Permission::whereIn('key', $keys)->where('reserved', true)->exists();
    }

    /**
     * @throws ResourceNotFoundException if $groupKey names no group
     */
    public function seed(string $key, string $name, string $groupKey, bool $reserved = false): Permission
    {
        $group = PermissionGroup::where('key', $groupKey)->first()
            ?? throw ResourceNotFoundException::for('PermissionGroup', $groupKey);

        return Permission::create([
            'key' => $key,
            'name' => $name,
            'permission_group_id' => $group->id,
            'sort' => (int) $group->permissions()->max('sort') + 1,
            'reserved' => $reserved,
        ]);
    }
}
