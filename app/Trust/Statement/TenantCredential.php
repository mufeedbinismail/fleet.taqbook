<?php

namespace App\Trust\Statement;

use App\Trust\Concern\HeldStatementConcern;
use App\Trust\Contract\HeldStatementContract;
use App\Trust\Enum\StatementKind;
use InvalidArgumentException;

final class TenantCredential extends Statement implements HeldStatementContract
{
    use HeldStatementConcern;

    public function __construct(
        public readonly string $uuid,
        public readonly string $fleetUrl,
        public readonly string $token,
        public readonly int $version,
    ) {
        if ($version < 1) {
            throw new InvalidArgumentException("Tenant credential [version] must be at least 1, {$version} given.");
        }
    }

    public static function kind(): StatementKind
    {
        return StatementKind::TenantCredential;
    }

    public static function sensitive(): bool
    {
        return true;
    }

    /**
     * @return array{uuid: string, fleet_url: string, token: string, ver: int}
     */
    public function body(): array
    {
        return [
            'uuid' => $this->uuid,
            'fleet_url' => $this->fleetUrl,
            'token' => $this->token,
            'ver' => $this->version,
        ];
    }

    public static function fromBody(array $body): static
    {
        return new self(
            uuid: $body['uuid'] ?? null,
            fleetUrl: $body['fleet_url'] ?? null,
            token: $body['token'] ?? null,
            version: $body['ver'] ?? null,
        );
    }
}
