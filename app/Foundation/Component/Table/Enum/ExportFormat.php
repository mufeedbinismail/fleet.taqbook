<?php

namespace App\Foundation\Component\Table\Enum;

enum ExportFormat: string
{
    case Csv = 'csv';

    case Xlsx = 'xlsx';

    public function extension(): string
    {
        return $this->value;
    }

    public function contentType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=UTF-8',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
