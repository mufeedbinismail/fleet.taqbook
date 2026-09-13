<?php

namespace App\Foundation\Component\Select\Control;

use App\Foundation\Component\Control\Control;
use App\Foundation\Component\Select\Collection\OptionCollection;
use App\Foundation\Component\Select\ValueObject\Option;
use App\Foundation\Component\Select\ValueObject\OptionChannel;

/**
 * A value picked from a declared set rather than typed.
 *
 * A fetched set is not read here, so nothing is held to it.
 */
abstract class ChoiceControl extends Control
{
    protected function __construct(protected readonly OptionChannel $channel) {}

    protected function isFetchedFromSource(): bool
    {
        return $this->channel->isFetchedFromSource();
    }

    /**
     * Whether a picked value is among what was declared; a set that was never listed here and a
     * choice nobody made are both beyond anything a set could be wrong about.
     */
    protected function accepts(mixed $value): bool
    {
        return $this->isAbsent($value) || $this->isFetchedFromSource() || $this->offers($value);
    }

    /**
     * @return array<int, mixed>
     */
    protected function values(mixed $raw): array
    {
        return is_array($raw) ? array_values($raw) : [$raw];
    }

    /**
     * @param  array<int|string, string>|list<Option>  $options
     */
    protected static function rows(array $options): OptionCollection
    {
        $rows = new OptionCollection;

        foreach ($options as $key => $option) {
            $rows[] = $option instanceof Option ? $option : Option::of($key, (string) $option);
        }

        return $rows;
    }

    /**
     * Compared as text, which is how a picked value arrives whatever it was declared as.
     */
    private function offers(mixed $value): bool
    {
        foreach ($this->channel->options() as $option) {
            if ($option->value === (string) $value) {
                return true;
            }
        }

        return false;
    }
}
