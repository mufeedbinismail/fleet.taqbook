<?php

namespace App\Foundation\Auth\Repository;

use App\Foundation\Auth\Model\PermissionGroup;
use Illuminate\Support\Collection;

class PermissionRepository
{
    /**
     * Every permission there is, grouped and in display order.
     *
     * Handed out as the models themselves rather than as a read model: the catalog is only ever
     * rendered server-side, and nothing over the wire ever carries it.
     *
     * @return Collection<int, PermissionGroup>
     */
    public function catalog(): Collection
    {
        return PermissionGroup::with('permissions')->orderBy('sort')->get();
    }
}
