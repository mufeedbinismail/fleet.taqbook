<?php

namespace App\Foundation\Auth\Http\Controller;

use App\Foundation\Auth\Action\DeleteUserAction;
use App\Foundation\Auth\Action\SaveUserAction;
use App\Foundation\Auth\Action\SetUserStatusAction;
use App\Foundation\Auth\Component\Select\RoleSelect;
use App\Foundation\Auth\Component\Table\UserTable;
use App\Foundation\Auth\Http\Request\SaveUserRequest;
use App\Foundation\Auth\Http\Request\SetUserStatusRequest;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Component\Table\ValueObject\InitialPage;
use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use App\Trade\Sale\Repository\SalesPointRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserRepository $repository,
        protected RoleSelect $roleSelect,
        protected OptionService $optionService,
        protected SalesPointRepository $salesPointRepository,
        protected TableBuilder $tables,
        protected UserTable $table,
    ) {}

    /**
     * The only server-rendered response. The roster arrives empty and fetches itself, so what is
     * staged here is the shape of the table and the lists the editor picks from.
     */
    public function index(Request $request, TableRequest $asked, TableRepository $tableRepository): View
    {
        $table = $this->table->table($this->tables);

        /*
            Read for the position the address asks for rather than for the first page, so a link
            somebody shared opens where they were, and for the roster's own opening position where
            the address asked for nothing. These are staff records, and resolving them here puts
            them in the page source rather than in a fetch response — deliberate, on a screen
            already gated to whoever administers accounts.
        */
        $state = $this->table->opensAt($asked->toState($table->name));

        return view('pages.auth.user-roster', [
            'title' => __('auth.user.title'),
            'definition' => $table,
            'initial' => InitialPage::of($tableRepository->page($table, $state), $state),
            'roles' => $this->optionService->channel($this->roleSelect, [RoleSelect::INACTIVE => 0]),
            'salesPoints' => $this->salesPointRepository->all(),
        ]);
    }

    public function store(SaveUserRequest $request, SaveUserAction $action): ResponseEnvelope
    {
        $this->save($request, $action);

        return ResponseEnvelope::ok(__('auth.user.notice.created'));
    }

    public function update(SaveUserRequest $request, SaveUserAction $action, User $user): ResponseEnvelope
    {
        $this->save($request, $action);

        return ResponseEnvelope::ok(__('auth.user.notice.updated'));
    }

    public function destroy(Request $request, DeleteUserAction $action, User $user): ResponseEnvelope
    {
        $actor = $request->user();

        $action->execute($user, $actor);

        return ResponseEnvelope::ok(__('auth.user.notice.deleted'));
    }

    public function status(SetUserStatusRequest $request, SetUserStatusAction $action, User $user): ResponseEnvelope
    {
        $inactive = $request->deactivates();
        $actor = $request->user();

        $action->execute($user, $inactive, $actor);

        return ResponseEnvelope::ok($inactive
            ? __('auth.user.notice.deactivated')
            : __('auth.user.notice.activated'));
    }

    protected function save(SaveUserRequest $request, SaveUserAction $action): void
    {
        $intent = $request->toIntent();

        $action->execute($intent);
    }
}
