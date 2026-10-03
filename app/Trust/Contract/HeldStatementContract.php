<?php

namespace App\Trust\Contract;

/**
 * A statement a tenant keeps one of per kind, replaced only by one for the same deployment that is not older.
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
}
