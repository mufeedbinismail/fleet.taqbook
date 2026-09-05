<?php

namespace App\Foundation\Shared\ValueObject;

/**
 * A span of days, either end of which may be open.
 */
final class Period
{
    public function __construct(
        public readonly ?DomainDateTime $from = null,
        public readonly ?DomainDateTime $to = null,
    ) {}

    /**
     * A span between whichever ends were given, or null where neither was: a span open at both
     * ends is every day there has ever been rather than a period anybody asked for.
     */
    public static function between(?DomainDateTime $from = null, ?DomainDateTime $to = null): ?self
    {
        return $from === null && $to === null ? null : new self($from, $to);
    }

    /**
     * How many days the span covers, or null while an end is open; both ends are inside it, so a
     * span of n reaches n-1 days past its first.
     */
    public function days(): ?int
    {
        return $this->from === null || $this->to === null
            ? null
            : $this->from->diffInDays($this->to) + 1;
    }
}
