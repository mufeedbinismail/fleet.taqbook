<?php

namespace App\Foundation\Auth\Http\Controller;

use App\Foundation\Auth\Action\DeleteRoleAction;
use App\Foundation\Auth\Action\SaveRoleAction;
use App\Foundation\Auth\Component\Select\RoleSelect;
use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Http\Request\RoleEditorRequest;
use App\Foundation\Auth\Http\Request\SaveRoleRequest;
use App\Foundation\Auth\Repository\PermissionRepository;
use App\Foundation\Auth\Service\RoleService;
use App\Foundation\Auth\ValueObject\RoleState;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\Envelope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleSelect $roles,
        protected OptionService $options,
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
            'roles' => $this->options->lookup($this->roles),
            'state' => $this->state($request->roleId(), $request)->toArray(),
        ]);
    }

    public function show(Request $request, int $role): Envelope
    {
        return Envelope::ok(data: $this->state($role, $request)->toArray());
    }

    public function store(SaveRoleRequest $request, SaveRoleAction $action): Envelope
    {
        $saved = $this->save($request, $action);

        return $this->payload($this->state($saved->id, $request), __('foundation.role.notice.created'));
    }

    public function update(SaveRoleRequest $request, SaveRoleAction $action, int $role): Envelope
    {
        $saved = $this->save($request, $action);

        return $this->payload($this->state($saved->id, $request), __('foundation.role.notice.updated'));
    }

    public function destroy(Request $request, DeleteRoleAction $action, int $role): Envelope
    {
        $this->refuse($action->validate($role));

        $action->execute($role);

        return $this->payload(
            $this->state(null, $request),
            __('foundation.role.notice.deleted'),
        );
    }

    /**
     * Checked and then written, in that order: past the check the same breach stops being something
     * to report and becomes a fault, which is why only this side of it produces a message.
     */
    protected function save(SaveRoleRequest $request, SaveRoleAction $action): Role
    {
        $intent = $request->toIntent();
        $actor = $request->user();

        $this->refuse($action->validate($intent, $actor));

        return $action->execute($intent, $actor);
    }

    protected function payload(RoleState $state, string $notice): Envelope
    {
        return Envelope::ok($notice, $state->toArray());
    }

    protected function state(?int $roleId, Request $request): RoleState
    {
        return $this->service->state($roleId, $request->user());
    }
}
