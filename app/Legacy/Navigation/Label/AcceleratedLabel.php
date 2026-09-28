<?php

namespace App\Legacy\Navigation\Label;

use App\Foundation\Navigation\Contract\LabelContract;
use App\Legacy\Navigation\Service\AcceleratorService;

/**
 * A label written in FrontAccounting's inline accelerator notation — "Sales &Order Entry".
 *
 * The accelerated character is marked inside the message itself, which forces the split to happen
 * after translation rather than before: each translation marks whichever character suits its own
 * wording, and that is seldom the one the English marked.
 */
final class AcceleratedLabel implements LabelContract
{
    public function __construct(public readonly string $message) {}

    public function text(): string
    {
        return AcceleratorService::split(__($this->message))[0];
    }

    public function accessKey(): ?string
    {
        return AcceleratorService::split(__($this->message))[1];
    }

    public function __toString(): string
    {
        return $this->text();
    }
}
