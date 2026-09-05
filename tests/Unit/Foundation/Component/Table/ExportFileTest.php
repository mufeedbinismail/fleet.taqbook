<?php

namespace Tests\Unit\Foundation\Component\Table;

use App\Foundation\Component\Table\Exporter\CsvExporter;
use App\Foundation\Component\Table\ValueObject\ColumnDefinition;
use App\Foundation\Component\Table\ValueObject\ExportSet;
use Illuminate\Support\LazyCollection;
use Tests\Concern\ReadsCsv;
use Tests\TestCase;

/**
 * The file itself, read back off disk. A table's rows leave the application here, and whatever is
 * wrong in them is wrong somewhere nobody can see the table any more.
 */
class ExportFileTest extends TestCase
{
    use ReadsCsv;

    /**
     * *Fails in either direction.* Left alone, a stored value is evaluated when the file is opened;
     * prefixed indiscriminately, every negative amount becomes text. `+1` and `-99` sit among the
     * attacks so only a rule reading whether a value is a number at all passes both halves.
     */
    public function test_a_value_that_would_be_read_as_a_formula_is_stored_as_text_and_a_number_is_not(): void
    {
        $written = $this->written(
            [new ColumnDefinition('value', 'Value')],
            [
                ['value' => '=1+1'],
                ['value' => "+cmd|'/c calc'!A0"],
                ['value' => '-SUM(A1)'],
                ['value' => '@import'],
                ['value' => "\tcmd"],
                ['value' => "\rcmd"],
                ['value' => -1234.56],
                ['value' => '-99'],
                ['value' => '+1'],
            ],
        );

        $this->assertSame([
            ["'=1+1"],
            ["'+cmd|'/c calc'!A0"],
            ["'-SUM(A1)"],
            ["'@import"],
            ["'\tcmd"],
            ["'\rcmd"],
            ['-1234.56'],
            ['-99'],
            ['+1'],
        ], $written);
    }

    /**
     * *Fails if* the row collapses to the values it happens to hold: every column past the gap is
     * then written under the heading of the one before it, and the file looks entirely plausible.
     */
    public function test_a_column_the_row_does_not_carry_writes_blank_and_shifts_nothing(): void
    {
        $written = $this->written(
            [
                new ColumnDefinition('code', 'Code'),
                new ColumnDefinition('name', 'Name'),
                new ColumnDefinition('cost', 'Cost'),
                new ColumnDefinition('units', 'Units'),
            ],
            [['code' => 'w-1', 'name' => 'Widget', 'units' => 'each']],
        );

        $this->assertSame([['w-1', 'Widget', '', 'each']], $written);
    }

    /**
     * The rows of the written file, headings dropped.
     *
     * @param  list<ColumnDefinition>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @return list<list<string>>
     */
    private function written(array $columns, array $rows): array
    {
        $path = tempnam(sys_get_temp_dir(), 'table-export-test-');

        (new CsvExporter)->write(
            new ExportSet($columns, LazyCollection::make($rows), count($rows)),
            $path,
        );

        $rows = $this->csvRowsAt($path);

        unlink($path);

        return $rows;
    }
}
