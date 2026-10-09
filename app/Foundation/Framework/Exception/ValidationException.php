<?php

namespace App\Foundation\Framework\Exception;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException as IlluminateValidationException;

class ValidationException extends IlluminateValidationException
{
    public function __construct(string $message, public readonly ?string $field = null)
    {
        $validator = Validator::make([], []);
        $validator->errors()->add($field ?? 'message', $message);

        parent::__construct($validator);
    }
}
