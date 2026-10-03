<?php

namespace App\Trust\Enum;

use App\Trust\Statement\Delegation;
use App\Trust\Statement\Message;
use App\Trust\Statement\Ping;
use App\Trust\Statement\Statement;

enum StatementKind: string
{
    case Delegation = 'delegation';
    case Message = 'message';
    case Ping = 'ping';

    /**
     * @return class-string<Statement>
     */
    public function statementClass(): string
    {
        return match ($this) {
            self::Delegation => Delegation::class,
            self::Message => Message::class,
            self::Ping => Ping::class,
        };
    }
}
