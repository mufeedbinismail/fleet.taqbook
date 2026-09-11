<?php

namespace App\Foundation\Auth\Exception;

use RuntimeException;

/**
 * A user invariant reached without being asked about first. Every message here reports a caller
 * that skipped the check, so none of them carries anything meant to be shown.
 */
class UserException extends RuntimeException
{
    public static function unchecked(string $field): self
    {
        return new self(sprintf('Executed without being validated first; the check on [%s] would have refused it.', $field));
    }
}
