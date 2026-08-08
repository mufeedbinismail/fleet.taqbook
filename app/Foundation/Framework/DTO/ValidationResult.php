<?php

namespace App\Foundation\Framework\DTO;

class ValidationResult
{
    private function __construct(
        public readonly bool $isValid,
        public readonly ?string $field = null,
        public readonly ?string $error = null
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function error(?string $field, ?string $errorMessage): self
    {
        return new self(false, $field, $errorMessage);
    }
}