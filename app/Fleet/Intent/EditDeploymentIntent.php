<?php

namespace App\Fleet\Intent;

use App\Fleet\Enum\Hosting;
use App\Foundation\Shared\ValueObject\DomainDateTime;

/**
 * Change what a deployment can be corrected about. Its number, its uuid, its alias and its status
 * are not here: the first two are assigned once, and the other two are each changed on their own
 * screen.
 */
final class EditDeploymentIntent
{
    public function __construct(
        public readonly int $debtorNo,
        public readonly Hosting $hosting,
        public readonly ?string $url,
        public readonly DomainDateTime $instanceCreatedDate,
    ) {}
}
