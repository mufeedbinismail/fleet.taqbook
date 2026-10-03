<?php

namespace App\Trust\Statement;

use App\Trust\Contract\StatementContract;
use App\Trust\Enum\StatementKind;
use App\Trust\Support\Encoding;
use InvalidArgumentException;
use JsonException;
use SodiumException;
use TypeError;

abstract class Statement implements StatementContract
{
    public static function sensitive(): bool
    {
        return false;
    }

    /**
     * The one way a statement is written, its kind included, so the bytes a signer signs are the bytes a reader checks.
     */
    final public function toEncodedString(): string
    {
        return Encoding::encode(json_encode(['kind' => static::kind()->value] + $this->body(), JSON_THROW_ON_ERROR));
    }

    /**
     * @throws InvalidArgumentException|JsonException|SodiumException when it cannot be read as this kind of statement
     */
    final public static function fromEncodedString(string $encoded): static
    {
        $body = json_decode(Encoding::decode($encoded), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($body)) {
            throw new InvalidArgumentException('Statement body must be a JSON object.');
        }

        $kind = is_string($body['kind'] ?? null) ? StatementKind::tryFrom($body['kind']) : null;

        if ($kind === null) {
            throw new InvalidArgumentException('Statement [kind] must be a known kind.');
        }

        $class = $kind->statementClass();

        if (! is_a($class, static::class, true)) {
            throw new InvalidArgumentException('Expected a ['.static::kind()->value."], [{$kind->value}] given.");
        }

        unset($body['kind']);

        // A statement's typed constructor is its field check, so a mistyped body is unreadable too.
        try {
            return $class::fromBody($body);
        } catch (TypeError $e) {
            throw new InvalidArgumentException($e->getMessage(), previous: $e);
        }
    }
}
