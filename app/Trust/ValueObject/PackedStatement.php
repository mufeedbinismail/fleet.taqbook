<?php

namespace App\Trust\ValueObject;

use App\Trust\Statement\Statement;
use App\Trust\Support\Encoding;
use InvalidArgumentException;

/**
 * @template-covariant T of Statement
 */
final class PackedStatement
{
    /**
     * @param  T  $statement
     */
    public function __construct(
        public readonly Statement $statement,
        public readonly string $signature,
    ) {}

    public function toEncodedString(): string
    {
        return Encoding::encode($this->statement->toEncodedString().'.'.Encoding::encode($this->signature));
    }

    /**
     * Decodes the text form only; nothing here says who signed it, which is PackingService::unpack()'s to check.
     *
     * @return self<Statement>
     */
    public static function fromEncodedString(string $encoded): self
    {
        $segments = explode('.', Encoding::decode($encoded));

        if (count($segments) !== 2) {
            throw new InvalidArgumentException(sprintf(
                'Packed statement must have 2 dot-separated segments, %d given.',
                count($segments),
            ));
        }

        return new self(Statement::fromEncodedString($segments[0]), Encoding::decode($segments[1]));
    }
}
