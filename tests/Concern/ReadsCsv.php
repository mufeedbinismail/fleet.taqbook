<?php

namespace Tests\Concern;

trait ReadsCsv
{
    /**
     * Every line of a written file, the headings first.
     *
     * @return list<list<string>>
     */
    private function csvAt(string $path): array
    {
        $handle = fopen($path, 'r');
        $read = [];

        while (($fields = fgetcsv($handle)) !== false) {
            $read[] = $fields;
        }

        fclose($handle);

        // A byte order mark is part of the first field rather than a line of its own.
        $read[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $read[0][0]);

        return $read;
    }

    /**
     * The rows of a written file, headings dropped.
     *
     * @return list<list<string>>
     */
    private function csvRowsAt(string $path): array
    {
        return array_values(array_slice($this->csvAt($path), 1));
    }
}
