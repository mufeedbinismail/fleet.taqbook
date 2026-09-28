<?php

namespace App\Foundation\Auth\Intent;

/**
 * Sign in as somebody. Carries only already-validated values, so nothing holding one of these needs
 * to check them again.
 */
final class LoginIntent
{
    public function __construct(
        public readonly string $login,
        public readonly string $password,
        public readonly string $ip,
    ) {}
}
