<?php

namespace App\Foundation\Component\Table\Service;

use App\Foundation\Component\Table\Contract\ExporterContract;
use App\Foundation\Component\Table\Enum\ExportFormat;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\Exporter\CsvExporter;
use App\Foundation\Component\Table\Exporter\XlsxExporter;
use App\Foundation\Component\Table\ValueObject\ExportSet;
use App\Foundation\Framework\DTO\ValidationResult;

/**
 * Which of the ways of writing an export answers for a format, and where what it writes ends up.
 */
class ExportService
{
    public function __construct(
        private readonly CsvExporter $csvExporter,
        private readonly XlsxExporter $xlsxExporter,
    ) {}

    public function for(ExportFormat $format): ExporterContract
    {
        return match ($format) {
            ExportFormat::Csv => $this->csvExporter,
            ExportFormat::Xlsx => $this->xlsxExporter,
        };
    }

    public function validate(ExportSet $set, ExportFormat $format): ValidationResult
    {
        return $this->for($format)->validate($set);
    }

    /**
     * A file rather than the response body, so a failure part-way through the rows can still be
     * reported as one rather than arriving after the first byte has gone out.
     *
     * @return string the absolute path of a temporary file
     *
     * @throws TableException if the set is beyond what the format can carry
     */
    public function write(ExportSet $set, ExportFormat $format): string
    {
        $path = tempnam(sys_get_temp_dir(), 'table-export-');

        $this->for($format)->write($set, $path);

        return $path;
    }
}
