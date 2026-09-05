<?php

namespace App\Foundation\Component\Table\Support;

use App\Foundation\Component\Table\Enum\Direction;
use App\Foundation\Component\Table\ValueObject\ColumnSort;
use App\Foundation\Component\Table\ValueObject\Sort;

/**
 * The written form of an ordering, `-last_visit,name`, and the reading of it.
 *
 * The same expression reads two ways: its names are client keys where a client wrote it, and
 * columns where a table declared an ordering of its own.
 */
final class SortExpression
{
    /**
     * An ordering a client asked for, whose names are keys.
     *
     * @return list<Sort>
     */
    public static function parse(?string $expression): array
    {
        return array_map(
            fn (array $step) => new Sort($step['name'], $step['direction']),
            self::steps($expression),
        );
    }

    /**
     * A table's own ordering, whose names are columns.
     *
     * @return list<ColumnSort>
     */
    public static function parseColumns(?string $expression): array
    {
        return array_map(
            fn (array $step) => new ColumnSort($step['name'], $step['direction']),
            self::steps($expression),
        );
    }

    /**
     * @param  iterable<Sort>  $sorts
     */
    public static function express(iterable $sorts): string
    {
        $steps = [];

        foreach ($sorts as $sort) {
            $steps[] = $sort->direction->prefix().$sort->key;
        }

        return implode(',', $steps);
    }

    /**
     * Position carries precedence, so a name repeated later is dropped: the first mention decides
     * where a tie is broken, and a second cannot change it.
     *
     * @return list<array{name: string, direction: Direction}>
     */
    private static function steps(?string $expression): array
    {
        if ($expression === null || trim($expression) === '') {
            return [];
        }

        $steps = [];

        foreach (explode(',', $expression) as $step) {
            $step = trim($step);

            if ($step === '' || $step === '-') {
                continue;
            }

            $descending = str_starts_with($step, '-');
            $name = $descending ? substr($step, 1) : $step;

            $steps[$name] ??= [
                'name' => $name,
                'direction' => Direction::fromPrefix($descending ? '-' : ''),
            ];
        }

        return array_values($steps);
    }
}
