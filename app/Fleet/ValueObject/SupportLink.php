<?php

namespace App\Fleet\ValueObject;

use App\Fleet\Enum\SupportEntryDelivery;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Statement\Message;
use App\Trust\Statement\SupportEntry;
use App\Trust\ValueObject\PackedStatement;
use Illuminate\Contracts\Support\Arrayable;
use LogicException;

/**
 * @implements Arrayable<string, string>
 */
final class SupportLink implements Arrayable
{
    /**
     * @param  PackedStatement<Message>  $packed
     */
    public function __construct(
        public readonly SupportEntryDelivery $delivery,
        public readonly string $url,
        public readonly PackedStatement $packed,
    ) {
        if ($packed->statement->statement(SupportEntry::class) === null) {
            throw new LogicException("Message [{$packed->statement->id}] carries no [support.entry].");
        }
    }

    public function message(): Message
    {
        return $this->packed->statement;
    }

    public function entry(): SupportEntry
    {
        return $this->message()->statement(SupportEntry::class);
    }

    public function expiresAt(): DomainDateTime
    {
        return $this->message()->expiresAt;
    }

    /**
     * @return array{delivery: string, link: string, expires_at: string}
     */
    public function toArray(): array
    {
        return [
            'delivery' => $this->delivery->value,
            'link' => $this->url,
            'expires_at' => $this->expiresAt()->format(DomainDateTime::userDateTimeFormat()),
        ];
    }
}
