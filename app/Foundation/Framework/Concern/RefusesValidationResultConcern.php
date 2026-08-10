<?php

namespace App\Foundation\Framework\Concern;

use App\Foundation\Framework\DTO\ValidationResult;
use Illuminate\Validation\ValidationException;

/**
 * The crossing point, for whatever stands at it. Below here a refusal is a value describing what is
 * wrong and nothing knows what a request is; above it that refusal is a 422.
 *
 * Mixed in rather than inherited, because what answers an HTTP request is not always a controller —
 * and a refusal reaching the client differently depending on which of them raised it would be a
 * difference nobody chose.
 */
trait RefusesValidationResultConcern
{
    /**
     * Raises a domain refusal as a validation failure, and does nothing when there is none.
     *
     * Which field carries the message was settled by whoever produced the result, and a result
     * blaming no field in particular is reported against `message` so that something is always said.
     *
     * @throws ValidationException
     */
    protected function refuse(ValidationResult $result): void
    {
        if ($result->isValid) {
            return;
        }

        throw ValidationException::withMessages([
            $result->field ?? 'message' => [$result->error],
        ]);
    }
}
