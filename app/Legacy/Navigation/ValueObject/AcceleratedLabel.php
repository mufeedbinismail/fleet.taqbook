<?php

namespace App\Legacy\Navigation\ValueObject;

use App\Legacy\Navigation\Service\Accelerator;
use App\Foundation\Navigation\Contract\Label;

/**
 * A label written in FrontAccounting's inline accelerator notation — "Sales &Order Entry".
 *
 * The accelerated character is marked inside the message itself, which forces the split to happen
 * after translation rather than before: each translation marks whichever character suits its own
 * wording, and that is seldom the one the English marked.
 */
final class AcceleratedLabel implements Label
{
    public function __construct(public readonly string $message) {}

    public function text(): string
    {
        return Accelerator::split(__($this->message))[0];
    }

    public function accessKey(): ?string
    {
        return Accelerator::split(__($this->message))[1];
    }

    public function __toString(): string
    {
        return $this->text();
    }
}
