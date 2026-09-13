<?php

namespace App\Foundation\Component\Control\Concern;

/**
 * Telling a value that was never given from one that was given wrongly.
 */
trait ReadsRawValueConcern
{
    /**
     * Whether the raw value asks for nothing, which cannot be wrong however a control reads.
     */
    protected function isAbsent(mixed $raw): bool
    {
        return $raw === null || $raw === '' || $raw === [];
    }

    /**
     * An empty string is an untouched control rather than a value, so it reads as absent.
     */
    protected function scalar(mixed $value): int|float|string|bool|null
    {
        return is_scalar($value) && $value !== '' ? $value : null;
    }
}
