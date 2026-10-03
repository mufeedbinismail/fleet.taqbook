<?php

namespace App\Trust\Statement;

use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Collection\PackedStatementCollection;
use App\Trust\Enum\StatementKind;
use InvalidArgumentException;

/**
 * Statements told to one deployment for a while, under the delegation of the key that signed it.
 */
final class Message extends Statement
{
    /**
     * @param  string  $delegation  the packed delegation vouching for the key that signs the message
     * @param  PackedStatementCollection  $statements  each signed by the key that signs the message
     */
    public function __construct(
        public readonly string $issuer,
        public readonly string $audience,
        public readonly DomainDateTime $issuedAt,
        public readonly DomainDateTime $expiresAt,
        public readonly string $id,
        public readonly string $delegation,
        public readonly PackedStatementCollection $statements,
    ) {
        if ($statements->isEmpty()) {
            throw new InvalidArgumentException('Message [statements] must not be empty.');
        }
    }

    /**
     * @template T of Statement
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    public function statement(string $class): ?Statement
    {
        return $this->statements->find($class)?->statement;
    }

    public static function kind(): StatementKind
    {
        return StatementKind::Message;
    }

    /**
     * @return array{iss: string, aud: string, iat: int, exp: int, jti: string, del: string, statements: list<string>}
     */
    public function body(): array
    {
        return [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $this->issuedAt->getTimestamp(),
            'exp' => $this->expiresAt->getTimestamp(),
            'jti' => $this->id,
            'del' => $this->delegation,
            'statements' => $this->statements->toEncodedStrings(),
        ];
    }

    public static function fromBody(array $body): static
    {
        foreach (['iss', 'aud', 'jti', 'del'] as $field) {
            if (! is_string($body[$field] ?? null)) {
                throw new InvalidArgumentException("Message [{$field}] must be a string.");
            }
        }

        foreach (['iat', 'exp'] as $field) {
            if (! is_int($body[$field] ?? null)) {
                throw new InvalidArgumentException("Message [{$field}] must be a Unix timestamp.");
            }
        }

        if (! is_array($body['statements'] ?? null)) {
            throw new InvalidArgumentException('Message [statements] must be a list of packed statements.');
        }

        return new self(
            issuer: $body['iss'],
            audience: $body['aud'],
            issuedAt: DomainDateTime::createFromTimestamp($body['iat']),
            expiresAt: DomainDateTime::createFromTimestamp($body['exp']),
            id: $body['jti'],
            delegation: $body['del'],
            statements: PackedStatementCollection::fromEncodedStrings($body['statements']),
        );
    }
}
