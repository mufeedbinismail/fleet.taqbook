<?php

namespace App\Trust\Statement;

use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Entity\PublicKey;
use App\Trust\Enum\StatementKind;
use InvalidArgumentException;

/**
 * The root vouching for an operational key until a date.
 */
final class Delegation extends Statement
{
    public function __construct(
        public readonly string $kid,
        public readonly PublicKey $key,
        public readonly DomainDateTime $until,
    ) {}

    public static function kind(): StatementKind
    {
        return StatementKind::Delegation;
    }

    /**
     * @return array{kid: string, key: string, until: string}
     */
    public function body(): array
    {
        return [
            'kid' => $this->kid,
            'key' => $this->key->toEncodedString(),
            'until' => $this->until->toDateString(),
        ];
    }

    public static function fromBody(array $body): static
    {
        foreach (['kid', 'key', 'until'] as $field) {
            if (! is_string($body[$field] ?? null)) {
                throw new InvalidArgumentException("A delegation names a kid, a key and an until, each as a string. Its {$field} is not.");
            }
        }

        return new self(
            $body['kid'],
            PublicKey::fromEncodedString($body['key']),
            DomainDateTime::fromDateString($body['until']),
        );
    }
}
