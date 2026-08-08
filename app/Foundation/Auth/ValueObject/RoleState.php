<?php

namespace App\Foundation\Auth\ValueObject;

use App\Foundation\Auth\Entity\Role;

/**
 * The editable form for one role, or a blank one.
 *
 * A blank state is a state with no role rather than an absence, so the editor has one shape to
 * draw and never a branch for "nothing selected".
 */
final class RoleState
{
    /**
     * @param  Role|null  $role  null while a role is being composed but not yet saved
     * @param  list<string>  $permissions  granted permission keys
     * @param  bool  $own  whether this is the role the acting user holds
     */
    public function __construct(
        public readonly ?Role $role,
        public readonly array $permissions,
        public readonly bool $own,
    ) {}

    public static function blank(): self
    {
        return new self(role: null, permissions: [], own: false);
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function of(Role $role, array $permissions, bool $own): self
    {
        return new self(role: $role, permissions: $permissions, own: $own);
    }

    /**
     * Flat, because that is what the editor reads. A blank state answers with the empty values a
     * new role starts from rather than with nulls the client would have to translate.
     *
     * @return array{id: int|null, role_name: string, inactive: bool, permissions: list<string>, own: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->role?->id,
            'role_name' => $this->role?->name ?? '',
            'inactive' => $this->role?->inactive ?? false,
            'permissions' => $this->permissions,
            'own' => $this->own,
        ];
    }
}
