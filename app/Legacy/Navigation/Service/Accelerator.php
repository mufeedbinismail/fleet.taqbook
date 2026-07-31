<?php

namespace App\Legacy\Navigation\Service;

/**
 * Splits FrontAccounting's inline accelerator notation — "Sales &Order Entry" — into display text
 * and its access key.
 *
 * The match is greedy on purpose, so the *last* ampersand followed by an alphanumeric wins, and a
 * doubled ampersand collapses to a literal one. Both quirks are load-bearing: changing either
 * silently reassigns access keys across the menu.
 */
final class Accelerator
{
    /**
     * @return array{0: string, 1: string|null} display text, then the access key it marked
     */
    public static function split(string $label): array
    {
        $accessKey = null;

        if (preg_match('/(.*)&([a-zA-Z0-9])(.*)/', $label, $slices)) {
            $label = $slices[1].$slices[2].$slices[3];
            $accessKey = strtoupper($slices[2]);
        }

        return [str_replace('&&', '&', $label), $accessKey];
    }
}
