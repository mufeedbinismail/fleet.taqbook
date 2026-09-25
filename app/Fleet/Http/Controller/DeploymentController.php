<?php

namespace App\Fleet\Http\Controller;

use App\Fleet\Action\ChangeDeploymentStatusAction;
use App\Fleet\Action\EditDeploymentAction;
use App\Fleet\Action\EraseDeploymentAction;
use App\Fleet\Action\PingDeploymentAction;
use App\Fleet\Action\RegisterDeploymentAction;
use App\Fleet\Action\RemoveDeploymentAction;
use App\Fleet\Action\RenameDeploymentAction;
use App\Fleet\Component\Table\DeploymentTable;
use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Enum\Hosting;
use App\Fleet\Http\Request\ChangeDeploymentStatusRequest;
use App\Fleet\Http\Request\EditDeploymentRequest;
use App\Fleet\Http\Request\RegisterDeploymentRequest;
use App\Fleet\Http\Request\RenameDeploymentRequest;
use App\Fleet\Model\Deployment;
use App\Foundation\Component\Select\Service\OptionService;
use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Component\Table\ValueObject\InitialPage;
use App\Foundation\Framework\Http\Controller\Controller;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use App\Trade\Sale\Component\Select\CustomerSelect;
use Illuminate\Contracts\View\View;

class DeploymentController extends Controller
{
    /**
     * The only server-rendered response. The register arrives holding its opening page, and asks
     * the list endpoint for every page after that.
     */
    public function index(
        TableRequest $asked,
        TableRepository $tableRepository,
        TableBuilder $tables,
        DeploymentTable $table,
        OptionService $optionService,
        CustomerSelect $customerSelect,
    ): View {
        $definition = $table->table($tables);
        $state = $asked->toState($definition->name);

        return view('pages.fleet.deployment-register', [
            'title' => __('fleet.deployment.title'),
            'definition' => $definition,
            'initial' => InitialPage::of($tableRepository->page($definition, $state), $state),
            'customers' => $optionService->channel($customerSelect),
            'hostings' => Hosting::choices(),
            'statuses' => DeploymentStatus::choices(),
        ]);
    }

    public function store(RegisterDeploymentRequest $request, RegisterDeploymentAction $action): ResponseEnvelope
    {
        $action->execute($request->toIntent());

        return ResponseEnvelope::ok(__('fleet.deployment.notice.registered'));
    }

    public function update(EditDeploymentRequest $request, EditDeploymentAction $action, Deployment $deployment): ResponseEnvelope
    {
        $action->execute($deployment, $request->toIntent());

        return ResponseEnvelope::ok(__('fleet.deployment.notice.saved'));
    }

    public function changeStatus(
        ChangeDeploymentStatusRequest $request,
        ChangeDeploymentStatusAction $action,
        Deployment $deployment,
    ): ResponseEnvelope {
        $action->execute($deployment, $request->toIntent());

        return ResponseEnvelope::ok(__('fleet.deployment.notice.status_changed'));
    }

    public function rename(
        RenameDeploymentRequest $request,
        RenameDeploymentAction $action,
        Deployment $deployment,
    ): ResponseEnvelope {
        $action->execute($deployment, $request->alias());

        return ResponseEnvelope::ok(__('fleet.deployment.notice.renamed'));
    }

    public function ping(PingDeploymentAction $action, Deployment $deployment): ResponseEnvelope
    {
        $outcome = $action->execute($deployment);

        return $outcome === DeliveryOutcome::Reached
            ? ResponseEnvelope::ok(__('fleet.deployment.notice.reached', ['alias' => $deployment->alias]))
            : ResponseEnvelope::failed($outcome->label());
    }

    public function destroy(RemoveDeploymentAction $action, Deployment $deployment): ResponseEnvelope
    {
        $action->execute($deployment);

        return ResponseEnvelope::ok(__('fleet.deployment.notice.removed'));
    }

    public function erase(EraseDeploymentAction $action, Deployment $deployment): ResponseEnvelope
    {
        $action->execute($deployment);

        return ResponseEnvelope::ok(__('fleet.deployment.notice.erased'));
    }
}
