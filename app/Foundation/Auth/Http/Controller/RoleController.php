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
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleSelect $select,
        protected OptionService $optionService,
        protected PermissionRepository $permissionRepository,
        protected RoleService $service,
    ) {}

    /**
     * The only server-rendered response. ?role= names which role opens, so the address the editor
     * keeps in the bar is one that can be shared and reloaded into the same place.
     */
    public function index(RoleEditorRequest $request): View
    {
        return view('pages.auth.security-role', [
            'title' => __('auth.role.title'),
            'groups' => $this->permissionRepository->catalog(),
            'roles' => $this->optionService->lookup($this->select),
            'state' => $this->state($request->uuid(), $request)->toArray(),
        ]);
    }

    public function show(Request $request, string $role): ResponseEnvelope
    {
        return ResponseEnvelope::ok(data: $this->state($role, $request)->toArray());
    }

    public function store(SaveRoleRequest $request, SaveRoleAction $action): ResponseEnvelope
    {
        $saved = $this->save($request, $action);

        return $this->payload($this->state($saved->uuid, $request), __('auth.role.notice.created'));
    }

    public function update(SaveRoleRequest $request, SaveRoleAction $action, string $role): ResponseEnvelope
    {
        $saved = $this->save($request, $action);

        return $this->payload($this->state($saved->uuid, $request), __('auth.role.notice.updated'));
    }

    public function destroy(Request $request, DeleteRoleAction $action, string $role): ResponseEnvelope
    {
        $action->execute($role);

        return $this->payload(
            $this->state(null, $request),
            __('auth.role.notice.deleted'),
        );
    }

    protected function save(SaveRoleRequest $request, SaveRoleAction $action): Role
    {
        $intent = $request->toIntent();
        $actor = $request->user();

        return $action->execute($intent, $actor);
    }

    protected function payload(RoleState $state, string $notice): ResponseEnvelope
    {
        return ResponseEnvelope::ok($notice, $state->toArray());
    }

    protected function state(?string $uuid, Request $request): RoleState
    {
        return $this->service->state($uuid, $request->user());
    }
}
