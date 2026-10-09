<?php

namespace App\Foundation\Component\Table\Contract;

use App\Foundation\Component\Table\ValueObject\ExportSet;
use App\Foundation\Framework\Exception\ValidationException;

/**
 * One way of writing a set of rows out as a file, bytes only.
 */
interface ExporterContract
{
    /**
     * @throws ValidationException if the set is beyond what this format can carry
     */
    public function validate(ExportSet $set): void;

    public function write(ExportSet $set, string $path): void;
}
