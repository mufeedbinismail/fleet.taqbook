<?php

namespace App\Foundation\Http\Controller;

use App\Shared\DTO\ValidationResult;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Raises a domain refusal as a validation failure, and does nothing when there is none.
     *
     * This is the crossing point: below it a refusal is a ValidationResult and nothing knows what a
     * request is; above it the refusal is a 422. Which field carries the message was settled by
     * whoever produced the result, and a result blaming no field in particular is reported against
     * `message` so that something is always said.
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
