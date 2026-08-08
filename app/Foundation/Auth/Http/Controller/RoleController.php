<?php

namespace App\Foundation\Auth\Http\Controller;

use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Http\Request\RoleEditorRequest;
use App\Foundation\Auth\Http\Request\SaveRoleRequest;
use App\Foundation\Auth\Repository\PermissionRepository;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\Service\RoleService;
use App\Foundation\Auth\ValueObject\RoleState;
use App\Foundation\Framework\Http\Controller\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleRepository $roles,
        protected PermissionRepository $permissions,
        protected RoleService $service,
    ) {}

    /**
     * The only server-rendered response. ?role= names which role opens, so the address the editor
     * keeps in the bar is one that can be shared and reloaded into the same place.
     */
    public function index(RoleEditorRequest $request): View
    {
        return view('pages.foundation.security-role', [
            'title' => __('foundation.role.title'),
            'groups' => $this->permissions->catalog(),
            'roles' => $this->roleList(),
            'state' => $this->state($request->roleId(), $request)->toArray(),
        ]);
    }

    public function show(Request $request, int $role): JsonResponse
    {
        return response()->json(['state' => $this->state($role, $request)->toArray()]);
    }

    public function store(SaveRoleRequest $request): JsonResponse
    {
        $saved = $this->save($request);

        return $this->payload($this->state($saved->id, $request), __('foundation.role.notice.created'));
    }

    public function update(SaveRoleRequest $request, int $role): JsonResponse
    {
        $saved = $this->save($request);

        return $this->payload($this->state($saved->id, $request), __('foundation.role.notice.updated'));
    }

    public function destroy(Request $request, int $role): JsonResponse
    {
        $this->refuse($this->service->validateDelete($role));

        $this->service->delete($role);

        return $this->payload(
            $this->state(null, $request),
            __('foundation.role.notice.deleted'),
        );
    }

    /**
     * Checked and then written, in that order: past the check the same breach stops being something
     * to report and becomes a fault, which is why only this side of it produces a message.
     */
    protected function save(SaveRoleRequest $request): Role
    {
        $intent = $request->toIntent();
        $actor = $this->actor($request);

        $this->refuse($this->service->validateSave($intent, $actor));

        return $this->service->save($intent, $actor);
    }

    /**
     * Mutations ship the refreshed role list alongside the new state: the picker has to learn about
     * a role that was just created, renamed, or deleted, and it holds every role at once so that
     * "Show inactive" stays a client-side filter.
     */
    protected function payload(RoleState $state, string $notice): JsonResponse
    {
        return response()->json([
            'state' => $state->toArray(),
            'roles' => $this->roleList(),
            'notice' => $notice,
        ]);
    }

    protected function state(?int $roleId, Request $request): RoleState
    {
        return $this->service->state($roleId, $this->actor($request));
    }

    /**
     * The role held by whoever is asking, which is what decides whether a role reads as their own
     * and whether a save is allowed to strip its access.
     */
    protected function actor(Request $request): ?int
    {
        return $request->user()?->role_id;
    }

    /**
     * @return list<array{id: int, role_name: string, inactive: bool}>
     */
    protected function roleList(): array
    {
        return array_map(fn (Role $role) => $role->toArray(), $this->roles->all());
    }
}
