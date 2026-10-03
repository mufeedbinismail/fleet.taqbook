<?php

namespace App\Trust\Contract;

/**
 * A statement an install keeps one of per kind, replaced only by a higher version.
 */
interface HeldStatementContract extends StatementContract
{
    /**
     * The deployment it is about.
     */
    public function uuid(): string;

    /**
     * Rises with every issue of its kind for the deployment.
     */
    public function version(): int;

    /**
     * Whether it carries a secret.
     */
    public static function sensitive(): bool;
}
