<?php

namespace App\Foundation\Component\Select\Control;

use App\Foundation\Component\Control\Contract\ScalarControl;
use App\Foundation\Component\Control\Enum\ControlName;
use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\ValueObject\Option;
use App\Foundation\Component\Select\ValueObject\OptionChannel;
use App\Foundation\Component\Select\ValueObject\OptionSource;
use App\Foundation\Framework\DTO\ValidationResult;

/**
 * One value picked from a declared set.
 */
final class SelectControl extends ChoiceControl implements ScalarControl
{
    /**
     * A set small enough to be declared in full.
     *
     * @param  array<int|string, string>|list<Option>  $options  `value => label`, or rows already
     *                                                           named; at least one either way
     *
     * @throws SelectException if there is nothing to choose from
     */
    public static function simple(array $options): self
    {
        return new self(OptionChannel::inline(self::rows($options)));
    }

    /**
     * A set too large to list, read from somewhere else as it is searched.
     */
    public static function lookup(OptionSource $source): self
    {
        return new self(OptionChannel::fromSource($source));
    }

    public static function from(OptionChannel $channel): self
    {
        return new self($channel);
    }

    public function config(): array
    {
        return [
            ...$this->channel->toArray(),
            'control' => ($this->isFetchedFromSource() ? ControlName::Lookup : ControlName::Select)->value,
            'multiple' => false,
        ];
    }

    protected function check(string $field, mixed $raw): ValidationResult
    {
        if (is_array($raw)) {
            return ValidationResult::error($field, __('foundation.select.error.not_one_value'));
        }

        return $this->accepts($raw)
            ? ValidationResult::success()
            : ValidationResult::error($field, __('foundation.select.error.not_a_choice'));
    }

    public function read(mixed $raw): int|float|string|bool|null
    {
        return $this->scalar($raw);
    }
}
