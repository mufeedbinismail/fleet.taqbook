<?php

namespace App\Foundation\Shared\ValueObject;

use App\Foundation\Shared\Enum\SystemType;
use InvalidArgumentException;
use Stringable;

final class TypedId implements Stringable
{
    const SEPARATOR = '-';

    public function __construct(
        public readonly SystemType $type,
        public readonly ?string $id = null
    ) {}

    public function isExisting(): bool
    {
        return ! $this->isNew();
    }

    public function isNew(): bool
    {
        return empty($this->id);
    }

    public static function tryFromString(string $provided): ?static
    {
        $result = explode(self::SEPARATOR, $provided, 2);

        if (count($result) < 2 || ! SystemType::tryFrom($result[0])) {
            return null;
        }

        return new self(SystemType::from($result[0]), $result[1]);
    }

    public static function fromString(string $provided): static
    {
        $result = static::tryFromString($provided);

        if (! $result) {
            throw new InvalidArgumentException(
                'Failed to dismantle string. Input must be a delimited string where the first part: '.
                "a valid SystemType enum. Provided: '{$provided}'"
            );
        }

        return $result;
    }

    public function toString(): string
    {
        return $this->type->value.self::SEPARATOR.$this->id;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function isEqualTo(TypedId $other)
    {
        return $this->type->value == $other->type->value && $this->id == $other->id;
    }

    public static function make($type, $id = null)
    {
        if (! $type instanceof SystemType) {
            if (! is_numeric($type) || ! SystemType::tryFrom((int) $type)) {
                throw new InvalidArgumentException(
                    "Failed to recognize a valid SystemType enum. Provided: '{$type}'"
                );
            }
            $type = SystemType::from($type);
        }

        return new static($type, ((string) $id) ?: null);
    }
}
