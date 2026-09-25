<?php

namespace App\Fleet\Action;

use App\Fleet\DTO\DeliveryResult;
use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Exception\DeploymentException;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Fleet\Service\MessageSigningService;
use App\Foundation\Framework\DTO\ValidationResult;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Statement\Statement;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpClient;

abstract class SendMessageAction
{
    public function __construct(
        private readonly DeploymentRepository $deploymentRepository,
        private readonly MessageSigningService $signer,
        private readonly HttpClient $http,
        private readonly Application $app,
    ) {}

    /**
     * A deployment with no address has nothing to be reached at, which is an answer rather than a failure.
     */
    public function validate(Deployment $deployment): ValidationResult
    {
        if (blank($deployment->url)) {
            return ValidationResult::error('url', __('fleet.deployment.error.no_address'));
        }

        $scheme = strtolower((string) parse_url($deployment->url, PHP_URL_SCHEME));

        if ($scheme !== 'https' && ! ($scheme === 'http' && $this->app->isLocal())) {
            return ValidationResult::error('url', __('fleet.deployment.error.insecure_address'));
        }

        return $this->signer->validate();
    }

    /**
     * A redirect is refused rather than followed: only the dialled address may answer.
     *
     * @throws DeploymentException if validate() would have refused it
     */
    final protected function deliver(Deployment $deployment, Statement $statement): DeliveryOutcome
    {
        $result = $this->validate($deployment);

        if (! $result->isValid) {
            throw new DeploymentException((string) $result->error);
        }

        $packed = $this->signer->sign([$statement], to: $deployment, ttl: config('fleet.message.lifetime_minutes'));

        try {
            $response = $this->http
                ->connectTimeout(config('fleet.message.connect_timeout_seconds'))
                ->timeout(config('fleet.message.timeout_seconds'))
                ->acceptJson()
                ->withoutRedirecting()
                ->post(rtrim($deployment->url, '/').config('fleet.message.path'), [
                    'message' => $packed->toEncodedString(),
                ]);
        } catch (ConnectionException|TransferException) {
            // Laravel wraps only a failed connect; a failed TLS handshake still arrives as Guzzle's own.
            return DeliveryOutcome::Unreachable;
        }

        if (! $response->successful()) {
            return DeliveryOutcome::Refused;
        }

        $outcome = $this->respond(new DeliveryResult($deployment, $packed->statement, $response));

        if ($outcome === DeliveryOutcome::Reached) {
            $this->deploymentRepository->recordPush($deployment, DomainDateTime::now());
        }

        return $outcome;
    }

    protected function respond(DeliveryResult $result): DeliveryOutcome
    {
        return $result->response->json('jti') === $result->message->id
            ? DeliveryOutcome::Reached
            : DeliveryOutcome::WrongAnswer;
    }
}
