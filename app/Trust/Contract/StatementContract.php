<?php

namespace App\Trust\Contract;

use App\Trust\Enum\StatementKind;

interface StatementContract
{
    public static function kind(): StatementKind;

    /**
     * @return array<string, mixed>
     */
    public function body(): array;

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromBody(array $body): static;
}
