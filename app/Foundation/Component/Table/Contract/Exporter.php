<?php

namespace App\Foundation\Component\Table\Contract;

use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\ValueObject\ExportSet;
use App\Foundation\Framework\DTO\ValidationResult;

/**
 * One way of writing a set of rows out as a file, bytes only.
 */
interface Exporter
{
    public function validate(ExportSet $set): ValidationResult;

    /**
     * @throws TableException if the set is beyond what this format can carry
     */
    public function write(ExportSet $set, string $path): void;
}
