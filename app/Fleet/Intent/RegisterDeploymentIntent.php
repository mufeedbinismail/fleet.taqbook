<?php

namespace App\Fleet\Intent;

use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Enum\Hosting;
use App\Foundation\Shared\ValueObject\DomainDateTime;

/**
 * Put a customer's install on the register. Carries only already-validated values, and none of
 * what the register itself mints.
 */
final class RegisterDeploymentIntent
{
    public function __construct(
        public readonly int $debtorNo,
        public readonly string $alias,
        public readonly DeploymentStatus $status,
        public readonly Hosting $hosting,
        public readonly ?string $url,
        public readonly DomainDateTime $instanceCreatedDate,
    ) {}
}
