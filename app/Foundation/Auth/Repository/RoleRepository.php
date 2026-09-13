<?php

namespace App\Foundation\Auth\Repository;

use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Intent\SaveRoleIntent;
use App\Foundation\Auth\Model\Permission;
use App\Foundation\Auth\Model\Role as RoleRecord;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

class RoleRepository
{
    public function find(int $id): ?Role
    {
        $record = RoleRecord::find($id);

        return $record === null ? null : Role::of($record);
    }

    /**
     * The role's granted permission keys, empty when there is no such role.
     *
     * @return list<string>
     */
    public function grantedKeys(int $id): array
    {
        return RoleRecord::find($id)?->permissions()->pluck('key')->values()->all() ?? [];
    }

    public function isAssigned(int $id): bool
    {
        return RoleRecord::find($id)?->users()->exists() ?? false;
    }

    /**
     * Whether some other role already carries this name. $excludingId is the role being saved, so
     * a role keeps its own name without tripping over itself.
     */
    public function nameTaken(string $name, ?int $excludingId): bool
    {
        return RoleRecord::where('role', $name)
            ->when($excludingId !== null, fn ($query) => $query->where('id', '!=', $excludingId))
            ->exists();
    }

    /**
     * Creates or overwrites a role together with its grants, in a transaction because a role
     * written without its grants is a role that silently locks people out.
     *
     * @throws ResourceNotFoundException if $intent->roleId names no role
     */
    public function save(SaveRoleIntent $intent): Role
    {
        return DB::transaction(function () use ($intent) {
            $record = $intent->isEditing()
                ? RoleRecord::find($intent->roleId) ?? throw ResourceNotFoundException::for('Role', $intent->roleId)
                : new RoleRecord;

            $record->role = $intent->name;
            $record->inactive = (int) $intent->inactive;
            $record->save();

            /*
                Keys with no permission row behind them are dropped rather than failing the save: a
                grant naming nothing grants nothing, so what survives the sync is already the whole
                of what the role can do.
            */
            $record->permissions()->sync(
                Permission::whereIn('key', $intent->permissions)->pluck('id')
            );

            return Role::of($record);
        });
    }

    public function delete(int $id): void
    {
        RoleRecord::destroy($id);
    }
}
