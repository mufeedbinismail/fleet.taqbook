<?php

namespace App\Fleet\Constant;

final class DeploymentAlias
{
    // Deliberately does not allow all-numeric aliases, to avoid collision with deployment number
    public const PATTERN = '^(?![0-9]+$)[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*$';

    public const LENGTH = 60;
}
