<?php

namespace App\Foundation\Component\Table\Support;

use App\Foundation\Component\Table\Enum\Stick;

/**
 * Where a table's pinned columns sit: each one's distance from its edge is the widths between them
 * summed as `calc()` rather than as numbers, which is what lets a column measured in rem be pinned
 * beside one measured in px.
 */
final class StickyColumns
{
    /**
     * @param  list<array<string, mixed>>  $columns  in the order they are drawn
     * @param  string|null  $trailing  the width of a pinned column past the last declared one, from
     *                                 which the end run is then measured
     * @return array<string, array{side: string, away: string, width: string, edge: bool}> keyed by
     *                                                                                     column key
     */
    public static function placed(array $columns, ?string $trailing = null): array
    {
        return self::run($columns, Stick::Start, '0')
            + self::run(array_reverse($columns), Stick::End, $trailing ?? '0');
    }

    /**
     * One edge's run, walked outward-in, so the last one reached is the innermost and the only
     * one carrying `edge`.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return array<string, array{side: string, away: string, width: string, edge: bool}>
     */
    private static function run(array $columns, Stick $edge, string $away): array
    {
        $placed = [];

        foreach ($columns as $column) {
            if (($column['appearance']['sticky'] ?? null) !== $edge->value) {
                break;
            }

            $placed[$column['key']] = [
                'side' => $edge->value,
                'away' => $away,
                'width' => $column['appearance']['width'],
                'edge' => false,
            ];

            $width = $column['appearance']['width'];

            $away = $away === '0' ? $width : "calc({$away} + {$width})";
        }

        if ($placed !== []) {
            $placed[array_key_last($placed)]['edge'] = true;
        }

        return $placed;
    }
}
