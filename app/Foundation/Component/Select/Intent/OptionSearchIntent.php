<?php

namespace App\Foundation\Component\Select\Intent;

/**
 * Fetch one page of choosable options.
 *
 * Every value here has already been through validation, filters included — a repository holding
 * one of these can put it straight into a query.
 */
final class OptionSearchIntent
{
    public const PER_PAGE = 25;

    public const MAX_PER_PAGE = 100;

    /**
     * @param  string  $search  the typed term, already trimmed; empty means unfiltered
     * @param  list<string>  $selected  values the client holds and wants a verdict on
     * @param  array<string, mixed>  $filters  the consuming screen's own narrowing, validated but
     *                                         still in the strings a query string is made of
     */
    public function __construct(
        public readonly string $search,
        public readonly int $page,
        public readonly int $perPage,
        public readonly array $selected,
        public readonly array $filters,
    ) {}

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function isSearching(): bool
    {
        return $this->search !== '';
    }

    /**
     * Whether any held value was named for a verdict. Nothing named is nothing to answer, and so
     * no second query to run.
     */
    public function wantsVerdict(): bool
    {
        return $this->selected !== [];
    }

    public function filter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * A narrowing whose legal answers are a closed set, read back as one of them.
     *
     * The values here are still the strings a query string is made of, so every definition
     * narrowing by an enum had the same cast and the same `tryFrom` to write before it could switch
     * on anything. Absent and unrecognised come back the same way — a name that is not a case is not
     * a narrowing, and a list is wide rather than empty when it is asked something it cannot answer.
     *
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    public function enum(string $key, string $enum): ?\BackedEnum
    {
        $value = $this->filter($key);

        return is_string($value) || is_int($value) ? $enum::tryFrom($value) : null;
    }
}
