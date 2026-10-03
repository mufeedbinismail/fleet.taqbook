<?php

namespace App\Trust\Statement;

use App\Trust\Concern\HeldStatementConcern;
use App\Trust\Contract\HeldStatementContract;
use App\Trust\Enum\StatementKind;
use InvalidArgumentException;

final class TenantIdentity extends Statement implements HeldStatementContract
{
    use HeldStatementConcern;

    public function __construct(
        public readonly string $uuid,
        public readonly int $number,
        public readonly string $alias,
        public readonly string $customer,
        public readonly int $version,
    ) {
        if ($version < 1) {
            throw new InvalidArgumentException("Tenant identity [version] must be at least 1, {$version} given.");
        }
    }

    public static function kind(): StatementKind
    {
        return StatementKind::TenantIdentity;
    }

    public static function sensitive(): bool
    {
        return false;
    }

    /**
     * @return array{uuid: string, number: int, alias: string, customer: string, ver: int}
     */
    public function body(): array
    {
        return [
            'uuid' => $this->uuid,
            'number' => $this->number,
            'alias' => $this->alias,
            'customer' => $this->customer,
            'ver' => $this->version,
        ];
    }

    public static function fromBody(array $body): static
    {
        return new self(
            uuid: $body['uuid'] ?? null,
            number: $body['number'] ?? null,
            alias: $body['alias'] ?? null,
            customer: $body['customer'] ?? null,
            version: $body['ver'] ?? null,
        );
    }
}
