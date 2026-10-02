<?php

namespace App\Fleet\Console;

use App\Fleet\Action\IssueIdentityAction;
use App\Fleet\Repository\DeploymentRepository;
use App\Foundation\Framework\Exception\ValidationException;
use App\Trust\Statement\TenantCredential;
use App\Trust\Statement\TenantIdentity;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class IssueIdentityCommand extends Command
{
    protected $signature = 'fleet:identity:issue
        {deployment : The deployment to identify, by its uuid, number or alias}';

    protected $description = 'Issue a signed identity and credential for a deployment, to carry to its install';

    protected $help = <<<'TEXT'
        Issues a new identity and credential for a deployment, signed into one
        payload to carry to the install. The install takes it with
        fleet:identity:install.

        Every issue raises both of the deployment's versions and replaces its
        token, so anything issued earlier stops being worth installing. A
        retired deployment is refused.

        Example:

          fleet:identity:issue acme-main > acme-main.identity.json

        Prints a JSON object:

          uuid            the deployment
          number          its number
          alias           its alias
          identity_ver    the identity's version
          credential_ver  the credential's version
          expires_at      when the payload stops being installable
          payload         the signed identity and credential, the one thing
                          to carry. It holds the tenant secret, so delete
                          any file it was written to once installed.
        TEXT;

    public function handle(DeploymentRepository $deploymentRepository, IssueIdentityAction $action): int
    {
        $stderr = $this->output->getErrorStyle();
        $key = $this->argument('deployment');

        $deployment = $deploymentRepository->findByKey($key);

        if ($deployment === null) {
            $stderr->error("No deployment has {$key} as its uuid, number or alias.");

            return self::FAILURE;
        }

        try {
            $action->validate($deployment);
        } catch (ValidationException $e) {
            $stderr->error("Not issued: {$e->getMessage()}");

            return self::FAILURE;
        }

        $packed = $action->execute($deployment);
        $message = $packed->statement;

        $identity = $message->statement(TenantIdentity::class);
        $credential = $message->statement(TenantCredential::class);

        $this->output->writeln(json_encode([
            'uuid' => $identity->uuid,
            'number' => $identity->number,
            'alias' => $identity->alias,
            'identity_ver' => $identity->version,
            'credential_ver' => $credential->version,
            'expires_at' => $message->expiresAt->toIso8601String(),
            'payload' => $packed->toEncodedString(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);

        $stderr->note([
            'Carry the payload to the install and run fleet:identity:install there.',
            'It contains the tenant secret. Issuing again replaces it.',
        ]);

        return self::SUCCESS;
    }
}
