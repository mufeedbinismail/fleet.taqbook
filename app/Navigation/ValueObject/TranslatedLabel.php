<?php

namespace App\Navigation\ValueObject;

use App\Navigation\Contract\Label;

/**
 * A label named by its translation key, with no accelerator.
 *
 * A message with no entry in the catalog translates to itself, so a literal reads the same way as
 * a key and no caller has to decide which of the two it is holding.
 */
final class TranslatedLabel implements Label
{
    public function __construct(public readonly string $message) {}

    public static function of(string $message): self
    {
        return new self($message);
    }

    public function text(): string
    {
        return __($this->message);
    }

    public function accessKey(): ?string
    {
        return null;
    }

    public function __toString(): string
    {
        return $this->text();
    }
}
