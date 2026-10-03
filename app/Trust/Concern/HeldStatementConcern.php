<?php

namespace App\Trust\Concern;

/**
 * For a statement that keeps its deployment in `$uuid` and its version in `$version`.
 */
trait HeldStatementConcern
{
    public function uuid(): string
    {
        return $this->uuid;
    }

    public function version(): int
    {
        return $this->version;
    }
}
