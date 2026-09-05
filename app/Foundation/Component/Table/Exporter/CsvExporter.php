<?php

namespace App\Foundation\Component\Table\Exporter;

use App\Foundation\Component\Table\Contract\Exporter;
use App\Foundation\Component\Table\ValueObject\ExportSet;
use App\Foundation\Framework\DTO\ValidationResult;
use DateTimeInterface;

/**
 * Comma-separated text, written a row at a time.
 *
 * Every value is text and nothing else: a spreadsheet opening this file evaluates whatever looks
 * like a formula, so a stored value would otherwise be a way to run something on whoever opens it.
 */
final class CsvExporter implements Exporter
{
    /**
     * Any size at all, since rows go out one at a time and none is held on to.
     */
    public function validate(ExportSet $set): ValidationResult
    {
        return ValidationResult::success();
    }

    public function write(ExportSet $set, string $path): void
    {
        $handle = fopen($path, 'w');

        // The byte order mark is what makes a spreadsheet read the file as UTF-8 rather than as
        // the local codepage, which mangles every accented name in it.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, $set->headings());

        foreach ($set->rows as $row) {
            fputcsv($handle, array_map(
                fn (mixed $value) => $this->neutralise($value),
                $set->values($row),
            ));
        }

        fclose($handle);
    }

    /**
     * A leading apostrophe is what a spreadsheet reads as "what follows is text", and does not
     * show. Numbers are left alone: a leading minus starts a formula but also every negative
     * amount, and prefixing those would corrupt the column somebody meant to add up.
     */
    private function neutralise(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
