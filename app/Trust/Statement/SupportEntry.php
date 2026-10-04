<?php

namespace App\Trust\Statement;

use App\Trust\Enum\StatementKind;

final class SupportEntry extends Statement
{
    /**
     * @param  string|null  $targetLogin  the one user the session may act as; null opens the roster instead
     */
    public function __construct(
        public readonly string $employeeUuid,
        public readonly string $employeeName,
        public readonly ?string $targetLogin,
    ) {}

    public static function kind(): StatementKind
    {
        return StatementKind::SupportEntry;
    }

    /**
     * @return array{employee_uuid: string, employee_name: string, target_login: string|null}
     */
    public function body(): array
    {
        return [
            'employee_uuid' => $this->employeeUuid,
            'employee_name' => $this->employeeName,
            'target_login' => $this->targetLogin,
        ];
    }

    public static function fromBody(array $body): static
    {
        return new self(
            employeeUuid: $body['employee_uuid'] ?? null,
            employeeName: $body['employee_name'] ?? null,
            targetLogin: $body['target_login'] ?? null,
        );
    }
}
