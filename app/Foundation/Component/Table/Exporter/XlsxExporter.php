<?php

namespace App\Foundation\Component\Table\Exporter;

use App\Foundation\Component\Table\Contract\Exporter;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\ValueObject\ExportSet;
use App\Foundation\Framework\DTO\ValidationResult;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * A spreadsheet, where a number stays a number and a date stays a date.
 *
 * The workbook is held whole in memory until it is written, so this is the one format with a size
 * past which it cannot be produced at all.
 */
final class XlsxExporter implements Exporter
{
    public function validate(ExportSet $set): ValidationResult
    {
        if ($set->total <= $this->limit()) {
            return ValidationResult::success();
        }

        return ValidationResult::error('export', __('foundation.table.error.export_too_large', [
            'limit' => number_format($this->limit()),
        ]));
    }

    public function write(ExportSet $set, string $path): void
    {
        if (! $this->validate($set)->isValid) {
            throw TableException::tooManyRowsToExport($set->total, $this->limit());
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($set->headings(), null, 'A1');

        $line = 2;

        foreach ($set->rows as $row) {
            $column = 1;

            foreach ($set->values($row) as $value) {
                $this->writeCell($sheet, $column++, $line, $value);
            }

            $line++;
        }

        (new Xlsx($spreadsheet))->save($path);

        // Released here rather than at the end of the request, the workbook being held whole.
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * Written as itself where the format has a reading for it and as an explicit string otherwise,
     * which is also what keeps a value that looks like a formula from becoming one.
     */
    private function writeCell(Worksheet $sheet, int $column, int $line, mixed $value): void
    {
        $cell = $sheet->getCell([$column, $line]);

        if ($value === null || $value === '') {
            return;
        }

        if ($value instanceof DateTimeInterface) {
            $cell->setValueExplicit(ExcelDate::PHPToExcel($value), DataType::TYPE_NUMERIC);

            // A value stamped at midnight is a date somebody chose; anything else is a moment, and
            // showing it without its time would round away the part that distinguishes two of them.
            $cell->getStyle()->getNumberFormat()->setFormatCode(
                $value->format('His') === '000000'
                    ? NumberFormat::FORMAT_DATE_YYYYMMDD
                    : NumberFormat::FORMAT_DATE_DATETIME_BETTER,
            );

            return;
        }

        if (is_int($value) || is_float($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);

            return;
        }

        if (is_bool($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_BOOL);

            return;
        }

        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
    }

    private function limit(): int
    {
        return (int) config('component.table.export.xlsx_rows');
    }
}
