<?php

namespace App\Foundation\Navigation\Contract;

use Stringable;

/**
 * Display text for a declaration, plus its optional keyboard accelerator.
 *
 * Implementations hold the message rather than the rendering of it, and resolve on every read
 * without memoising. That is what lets one label answer correctly under more than one locale, so a
 * declaration built once can be read by requests that disagree about language.
 */
interface Label extends Stringable
{
    public function text(): string;

    public function accessKey(): ?string;
}
