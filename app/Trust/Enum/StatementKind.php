<?php

namespace App\Trust\Enum;

use App\Trust\Statement\Delegation;
use App\Trust\Statement\Message;
use App\Trust\Statement\Ping;
use App\Trust\Statement\Statement;
use App\Trust\Statement\TenantCredential;
use App\Trust\Statement\TenantIdentity;

enum StatementKind: string
{
    case Delegation = 'delegation';
    case Message = 'message';
    case Ping = 'ping';
    case TenantIdentity = 'tenant.identity';
    case TenantCredential = 'tenant.credential';

    /**
     * @return class-string<Statement>
     */
    public function statementClass(): string
    {
        return match ($this) {
            self::Delegation => Delegation::class,
            self::Message => Message::class,
            self::Ping => Ping::class,
            self::TenantIdentity => TenantIdentity::class,
            self::TenantCredential => TenantCredential::class,
        };
    }
}
