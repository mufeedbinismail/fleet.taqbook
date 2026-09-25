<?php

namespace App\Fleet\Console;

use App\Fleet\Action\PingDeploymentAction;
use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Job\PingDeploymentJob;
use App\Fleet\Repository\DeploymentRepository;
use Illuminate\Console\Command;

class PingDeploymentCommand extends Command
{
    protected $signature = 'fleet:ping
        {deployment? : The deployment to ping, by its uuid, number or alias}
        {--all : Queue a ping to every deployment that has an address and is not retired}';

    protected $description = 'Check whether a deployment can be reached from the fleet';

    protected $help = <<<'TEXT'
        Sends a ping to a deployment's address and reports whether the
        deployment answered it.

        Examples:

          fleet:ping acme-main    ping one deployment, by its alias
          fleet:ping 42           the same, by its number
          fleet:ping --all        queue a ping to every deployment that has an
                                  address and is not retired

        Give either one deployment or --all, not both.

        With one deployment, the ping is sent straight away and the command
        waits for the answer. It prints one of:

          Reached <alias>.                          the deployment answered
          <alias>: Could not connect ...            nothing answered at its address
          <alias>: Its address refused ...          its address answered with an error
          <alias>: Its address answered, but ...    something answered, not the deployment
          Not attempted: <reason>                   the ping could not be sent, for
                                                    example because it has no address

        It exits with 0 only when the deployment was reached.

        With --all, the pings are queued for a queue worker to send, and the
        command only reports how many were queued.
        TEXT;

    public function handle(DeploymentRepository $repository, PingDeploymentAction $action): int
    {
        $key = $this->argument('deployment');

        if (($key === null) === ! $this->option('all')) {
            $this->error('Name one deployment, or pass --all.');

            return self::FAILURE;
        }

        if ($this->option('all')) {
            $pingable = $repository->pingableDeployments();

            foreach ($pingable as $deployment) {
                PingDeploymentJob::dispatch($deployment->uuid);
            }

            $this->info("Queued pings to {$pingable->count()} deployments.");

            return self::SUCCESS;
        }

        $deployment = $repository->findByKey($key);

        if ($deployment === null) {
            $this->error("No deployment has {$key} as its uuid, number or alias.");

            return self::FAILURE;
        }

        $result = $action->validate($deployment);

        if (! $result->isValid) {
            $this->error("Not attempted: {$result->error}");

            return self::FAILURE;
        }

        $outcome = $action->execute($deployment);

        if ($outcome !== DeliveryOutcome::Reached) {
            $this->error("{$deployment->alias}: {$outcome->label()}.");

            return self::FAILURE;
        }

        $this->info("Reached {$deployment->alias}.");

        return self::SUCCESS;
    }
}
