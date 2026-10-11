<?php

namespace App\Foundation\Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

final class ShortCode implements Stringable
{
    public const MAX_LENGTH = 20;

    public const PATTERN = '/\A[A-Za-z0-9]+([_-][A-Za-z0-9]+)*\z/';

    /**
     * @throws InvalidArgumentException when the value is not a code
     */
    public function __construct(public readonly string $value)
    {
        if (preg_match(self::PATTERN, $value) !== 1 || strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'A short code is letters and digits, joined by single "-" or "_", at most '.self::MAX_LENGTH.' characters.'
            );
        }
    }

    public static function tryFrom(mixed $provided): ?self
    {
        try {
            return new self($provided);
        } catch (InvalidArgumentException|\TypeError) {
            return null;
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
