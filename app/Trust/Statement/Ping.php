<?php

namespace App\Trust\Statement;

use App\Trust\Enum\StatementKind;

final class Ping extends Statement
{
    public static function kind(): StatementKind
    {
        return StatementKind::Ping;
    }

    /**
     * @return array{}
     */
    public function body(): array
    {
        return [];
    }

    public static function fromBody(array $body): static
    {
        return new self;
    }
}
