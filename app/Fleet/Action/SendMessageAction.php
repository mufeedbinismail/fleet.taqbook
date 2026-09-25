<?php

namespace App\Fleet\Action;

use App\Fleet\DTO\DeliveryResult;
use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Fleet\Service\MessageSigningService;
use App\Foundation\Framework\Exception\ValidationException;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Exception\TrustException;
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
    public function validate(Deployment $deployment): void
    {
        if (blank($deployment->url)) {
            throw new ValidationException(__('fleet.deployment.error.no_address'), 'url');
        }

        $scheme = strtolower((string) parse_url($deployment->url, PHP_URL_SCHEME));

        if ($scheme !== 'https' && ! ($scheme === 'http' && $this->app->isLocal())) {
            throw new ValidationException(__('fleet.deployment.error.insecure_address'), 'url');
        }

        $this->signer->validate();
    }

    /**
     * A redirect is refused rather than followed: only the dialled address may answer.
     *
     * @throws ValidationException if validate() refuses it
     * @throws TrustException if the server cannot sign
     */
    final protected function deliver(Deployment $deployment, Statement $statement): DeliveryOutcome
    {
        $this->validate($deployment);

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
