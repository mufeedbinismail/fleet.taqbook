<?php

namespace App\Foundation\Component\Select\ValueObject;

use App\Foundation\Component\Select\Collection\OptionCollection;
use App\Foundation\Component\Select\Exception\SelectException;
use Illuminate\Contracts\Support\Arrayable;

/**
 * How a select's choices reach the control: listed in full, or fetched from an address as they are
 * searched. Never both, and a listed channel offers at least one.
 */
final class OptionChannel implements Arrayable
{
    private function __construct(
        private readonly OptionCollection $options,
        private readonly ?OptionSource $source,
    ) {}

    /**
     * @throws SelectException if there is nothing to choose from
     */
    public static function inline(OptionCollection $options): self
    {
        if ($options->isEmpty()) {
            throw SelectException::offersNothing();
        }

        return new self($options, null);
    }

    public static function fromSource(OptionSource $source): self
    {
        return new self(new OptionCollection, $source);
    }

    public function isFetchedFromSource(): bool
    {
        return $this->source !== null;
    }

    public function options(): OptionCollection
    {
        return $this->options;
    }

    public function source(): ?OptionSource
    {
        return $this->source;
    }

    /**
     * @return array{options: list<array<string, mixed>>, source: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'options' => $this->options->toArray(),
            'source' => $this->source?->toArray(),
        ];
    }
}
