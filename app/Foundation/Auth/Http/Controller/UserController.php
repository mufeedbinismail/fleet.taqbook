<?php

namespace App\Foundation\Auth\Http\Controller;

use App\Foundation\Auth\Action\DeleteUserAction;
use App\Foundation\Auth\Action\SaveUserAction;
use App\Foundation\Auth\Action\SetUserStatusAction;
use App\Foundation\Auth\Component\Select\RoleSelect;
use App\Foundation\Auth\Component\Table\UserTable;
use App\Foundation\Auth\Http\Request\SaveUserRequest;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Component\Table\ValueObject\InitialPage;
use App\Foundation\Framework\Http\Controller\Controller;
use App\Trade\Sale\Repository\SalesPointRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserRepository $users,
        protected RoleSelect $roles,
        protected OptionService $options,
        protected SalesPointRepository $salesPoints,
        protected TableBuilder $tables,
        protected UserTable $table,
    ) {}

    /**
     * The only server-rendered response. The roster arrives empty and fetches itself, so what is
     * staged here is the shape of the table and the lists the editor picks from.
     */
    public function index(Request $request, TableRequest $asked, TableRepository $rows): View
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

        return view('pages.foundation.user-roster', [
            'title' => __('foundation.user.title'),
            'definition' => $table,
            'initial' => InitialPage::of($rows->page($table, $state), $state),
            'roles' => $this->options->channel($this->roles, [RoleSelect::INACTIVE => 0]),
            'salesPoints' => $this->salesPoints->all(),
        ]);
    }

    public function store(SaveUserRequest $request, SaveUserAction $action): JsonResponse
    {
        $this->save($request, $action);

        return $this->notice(__('foundation.user.notice.created'));
    }

    public function update(SaveUserRequest $request, SaveUserAction $action, int $user): JsonResponse
    {
        $this->save($request, $action);

        return $this->notice(__('foundation.user.notice.updated'));
    }

    public function destroy(Request $request, DeleteUserAction $action, int $user): JsonResponse
    {
        $actor = $request->user();

        $this->refuse($action->validate($user, $actor));

        $action->execute($user, $actor);

        return $this->notice(__('foundation.user.notice.deleted'));
    }

    public function status(Request $request, SetUserStatusAction $action, int $user): JsonResponse
    {
        $inactive = $request->boolean('inactive');
        $actor = $request->user();

        $this->refuse($action->validate($user, $inactive, $actor));

        $action->execute($user, $inactive, $actor);

        return $this->notice($inactive
            ? __('foundation.user.notice.deactivated')
            : __('foundation.user.notice.activated'));
    }

    /**
     * Checked and then written, in that order: past the check the same breach stops being something
     * to report and becomes a fault, which is why only this side of it produces a message.
     */
    protected function save(SaveUserRequest $request, SaveUserAction $action): void
    {
        $intent = $request->toIntent();

        $this->refuse($action->validate($intent));

        $action->execute($intent);
    }

    /**
     * Mutations answer with a message and nothing else. Anything more would be a second description
     * of the roster, competing with the one the list endpoint gives.
     */
    protected function notice(string $notice): JsonResponse
    {
        return response()->json(['notice' => $notice]);
    }
}
