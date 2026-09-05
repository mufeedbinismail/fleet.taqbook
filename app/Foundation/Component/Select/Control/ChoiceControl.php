<?php

namespace App\Foundation\Component\Select\Control;

use App\Foundation\Component\Control\Control;
use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\ValueObject\OptionSource;

/**
 * A value picked from a declared set rather than typed.
 *
 * A set is listed or it is fetched and there is no way to say both; a fetched one is not read here,
 * so nothing is held to it.
 */
abstract class ChoiceControl extends Control
{
    /**
     * @param  array<int|string, string>  $options  `value => label`
     *
     * @throws SelectException if there is nothing to choose from and nowhere to read choices from
     */
    protected function __construct(
        private readonly array $options,
        private readonly ?OptionSource $source,
    ) {
        if ($options === [] && $source === null) {
            throw SelectException::offersNothing();
        }
    }

    /**
     * @return array{options: array<int|string, string>, source: array<string, mixed>|null}
     */
    protected function offering(): array
    {
        return [
            'options' => $this->options,
            'source' => $this->source?->toArray(),
        ];
    }

    protected function fetched(): bool
    {
        return $this->source !== null;
    }

    /**
     * Whether a picked value is among what was declared; a set that was never listed here and a
     * choice nobody made are both beyond anything a set could be wrong about.
     */
    protected function accepts(mixed $value): bool
    {
        return $this->absent($value) || $this->options === [] || $this->offers($value);
    }

    /**
     * @return array<int, mixed>
     */
    protected function held(mixed $raw): array
    {
        return is_array($raw) ? array_values($raw) : [$raw];
    }

    /**
     * Compared as strings, numeric keys having been narrowed to int on their way into the array.
     */
    private function offers(mixed $value): bool
    {
        return in_array(
            (string) $value,
            array_map(fn (int|string $offered) => (string) $offered, array_keys($this->options)),
            true,
        );
    }
}
