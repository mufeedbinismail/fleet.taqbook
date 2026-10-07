<?php

namespace App\Foundation\Auth\Repository;

use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Intent\CreateRoleIntent;
use App\Foundation\Auth\Intent\UpdateRoleIntent;
use App\Foundation\Auth\Model\Permission;
use App\Foundation\Auth\Model\Role as RoleRecord;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;

class RoleRepository
{
    public function find(string $uuid): ?Role
    {
        $record = RoleRecord::find($uuid);

        return $record === null ? null : Role::of($record);
    }

    /**
     * The role's granted permission keys, empty when there is no such role.
     *
     * @return list<string>
     */
    public function grantedKeys(string $uuid): array
    {
        return RoleRecord::find($uuid)?->permissions()->pluck('key')->values()->all() ?? [];
    }

    public function isAssigned(string $uuid): bool
    {
        return RoleRecord::find($uuid)?->users()->exists() ?? false;
    }

    /**
     * Whether some other role already carries this name. $excludingUuid is the role being saved, so
     * a role keeps its own name without tripping over itself.
     */
    public function nameTaken(string $name, ?string $excludingUuid): bool
    {
        return RoleRecord::where('role', $name)
            ->when($excludingUuid !== null, fn ($query) => $query->where('uuid', '!=', $excludingUuid))
            ->exists();
    }

    /**
     * Written with its grants in a transaction, because a role written without its grants is a role
     * that silently locks people out.
     */
    public function create(CreateRoleIntent $intent): Role
    {
        return DB::transaction(function () use ($intent) {
            $record = new RoleRecord;

            if ($intent->uuid !== null) {
                $record->uuid = $intent->uuid;
            }

            $record->role = $intent->name;
            $record->inactive = (int) $intent->inactive;
            $record->reserved = (int) $intent->reserved;
            $record->save();

            $this->grant($record, $intent->permissions);

            return Role::of($record);
        });
    }

    /**
     * Overwritten with its grants in a transaction, for the same reason as a create.
     *
     * @throws ResourceNotFoundException if $intent->uuid names no role
     */
    public function update(UpdateRoleIntent $intent): Role
    {
        return DB::transaction(function () use ($intent) {
            $record = RoleRecord::find($intent->uuid) ?? throw ResourceNotFoundException::for('Role', $intent->uuid);

            $record->role = $intent->name;
            $record->inactive = (int) $intent->inactive;
            $record->save();

            $this->grant($record, $intent->permissions);

            return Role::of($record);
        });
    }

    /**
     * @param  list<string>  $keys
     */
    private function grant(RoleRecord $record, array $keys): void
    {
        /*
            Keys with no permission row behind them are dropped rather than failing the save: a
            grant naming nothing grants nothing, so what survives the sync is already the whole
            of what the role can do.
        */
        $record->permissions()->sync(
            Permission::whereIn('key', $keys)->pluck('id')
        );
    }

    public function delete(string $uuid): void
    {
        RoleRecord::destroy($uuid);
    }
}
