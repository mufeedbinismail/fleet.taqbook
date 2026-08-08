<?php

namespace App\Foundation\Navigation\ValueObject;

use App\Foundation\Navigation\Contract\Label;

/**
 * Text that is data rather than a message — a record's name, a document number.
 *
 * Kept apart from the translated kind because a message with no catalog entry translates to
 * itself, which makes running data through the translator look harmless right up until a customer
 * is called something a translator has an opinion about.
 */
final class PlainLabel implements Label
{
    public function __construct(public readonly string $text) {}

    public static function of(string $text): self
    {
        return new self($text);
    }

    public function text(): string
    {
        return $this->text;
    }

    public function accessKey(): ?string
    {
        return null;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
