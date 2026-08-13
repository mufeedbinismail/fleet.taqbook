<?php

namespace App\Foundation\Component\Select\ValueObject;

use App\Foundation\Component\Select\Support\DataAttributes;

/**
 * One choosable row.
 *
 * The value is a string because a `<select>` holds no other kind: a value that left as an integer
 * and came back as text would otherwise stop matching the row it came from.
 */
final class Option
{
    /**
     * What this row carries beyond its own name, for whoever reads the chosen option back.
     *
     * Written by whatever defines the list and never asked for by the screen consuming it: a screen
     * naming the columns it wanted would be choosing what the query selects, which is the same hole
     * as a screen handing over a condition of its own.
     *
     * @var array<string, string>
     */
    public readonly array $data;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly ?string $description = null,
        public readonly bool $disabled = false,

        // The heading this row sits under, where the list is one a reader navigates by section
        // rather than by scrolling — null where it has none, which is most lists.
        public readonly ?string $group = null,
        array $data = [],
    ) {
        $this->data = DataAttributes::of($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function of(
        int|string $value,
        string $label,
        ?string $description = null,
        bool $disabled = false,
        ?string $group = null,
        array $data = [],
    ): self {
        return new self((string) $value, $label, $description, $disabled, $group, $data);
    }

    /**
     * The second line, built from whichever of the parts a row actually has.
     *
     * How the parts are held apart is one decision about how a row reads, and a list spelling it out
     * for itself is a list that reads unlike the one beside it — which is what a person picking from
     * both of them notices before anything else.
     */
    public static function description(?string ...$parts): ?string
    {
        $said = array_filter($parts, static fn (?string $part) => $part !== null && $part !== '');

        // Emptiness decided by whether anything was said, not by what it says: a row whose only
        // part is a code of "0" has a second line, and a falsy test would swallow it.
        return $said === [] ? null : implode(' · ', $said);
    }
}
