<?php

namespace App\Fleet\Job;

use App\Fleet\Action\PingDeploymentAction;
use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Foundation\Framework\Exception\ValidationException;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use LogicException;

/**
 * One deployment's ping, on its own, so a tenant that hangs until the timeout holds up nobody else.
 */
class PingDeploymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $deploymentUuid) {}

    public function handle(DeploymentRepository $repository, PingDeploymentAction $action): void
    {
        $deployment = $repository->findByKey($this->deploymentUuid)
            ?? throw ResourceNotFoundException::for(Deployment::class, $this->deploymentUuid);

        // Skipped rather than failed: an undeliverable deployment is not a failed ping.
        try {
            $action->validate($deployment);
        } catch (ValidationException) {
            return;
        }

        $outcome = $action->execute($deployment);

        if ($outcome !== DeliveryOutcome::Reached) {
            $this->fail(new LogicException("{$deployment->alias}: {$outcome->label()}"));
        }
    }
}
