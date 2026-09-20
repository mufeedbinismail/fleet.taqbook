<?php

namespace App\Fleet\Intent;

use App\Fleet\Enum\DeploymentStatus;
use App\Foundation\Shared\ValueObject\DomainDateTime;

/**
 * Move a deployment to a status it is not already in, as of the moment it moved — which is not
 * necessarily the moment somebody got around to saying so.
 */
final class ChangeDeploymentStatusIntent
{
    public function __construct(
        public readonly DeploymentStatus $status,
        public readonly DomainDateTime $changedAt,
    ) {}
}
