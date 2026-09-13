<?php

namespace App\Foundation\Component\Select\Control;

use App\Foundation\Component\Control\Contract\SetControl;
use App\Foundation\Component\Control\Enum\ControlName;
use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\ValueObject\Option;
use App\Foundation\Component\Select\ValueObject\OptionChannel;
use App\Foundation\Component\Select\ValueObject\OptionSource;
use App\Foundation\Framework\DTO\ValidationResult;

/**
 * Several values picked from a declared set, which is a different thing to be handed than one of
 * them and not a way of being handed one.
 */
final class MultiSelectControl extends ChoiceControl implements SetControl
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

    /**
     * Named the same however few the choices, holding several being what the name has to carry.
     */
    public function config(): array
    {
        return [
            ...$this->channel->toArray(),
            'control' => ControlName::MultiSelect->value,
            'multiple' => true,
        ];
    }

    protected function check(string $field, mixed $raw): ValidationResult
    {
        foreach ($this->values($raw) as $value) {
            if (is_array($value)) {
                return ValidationResult::error($field, __('foundation.select.error.not_one_value'));
            }

            if (! $this->accepts($value)) {
                return ValidationResult::error($field, __('foundation.select.error.not_a_choice'));
            }
        }

        return ValidationResult::success();
    }

    /**
     * @return list<int|float|string|bool>|null
     */
    public function read(mixed $raw): ?array
    {
        $values = array_values(array_filter(
            array_map(fn (mixed $one) => $this->scalar($one), $this->values($raw)),
            fn (mixed $one) => $one !== null,
        ));

        return $values === [] ? null : $values;
    }
}
