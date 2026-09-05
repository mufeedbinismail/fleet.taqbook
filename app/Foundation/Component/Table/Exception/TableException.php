<?php

namespace App\Foundation\Component\Table\Exception;

use App\Foundation\Component\Table\Enum\Stick;
use RuntimeException;

/**
 * A table used in a way it cannot answer for: every one reports a mistake in how the table was
 * declared or driven, never anything a user typed, so none carries text meant to be read by one.
 */
class TableException extends RuntimeException
{
    public static function unmappableRow(string $type): self
    {
        return new self("A table row mapper returned {$type}, which cannot be read as a row.");
    }

    public static function unansweredDefinition(string $key): self
    {
        return new self("A table declares [{$key}], which its mapping does not answer for.");
    }

    public static function filterOnUndrawnDefinition(string $key): self
    {
        return new self("The definition [{$key}] draws no column and declares a filter, which has only a column heading to be offered from.");
    }

    public static function sortOnUndrawnDefinition(string $key): self
    {
        return new self("The definition [{$key}] draws no column and declares itself sortable, which has only a column heading to be asked from.");
    }

    public static function columnOrderedByTwoDefinitions(string $column): self
    {
        return new self("Two definitions are sortable by the column [{$column}], which leaves no one heading for an ordering by it to be reported under.");
    }

    public static function keyOfferedAsTwoFilters(string $key): self
    {
        return new self("The key [{$key}] is offered as a filter twice, which leaves a chip on it with two labels to choose between.");
    }

    public static function noQuery(): self
    {
        return new self('A table cannot be read before the query it is drawn from is given.');
    }

    public static function notDefinedOnRoute(string $path): self
    {
        return new self("The route [{$path}] does not name a table definition.");
    }

    public static function unnamed(): self
    {
        return new self('A table declares no name, which is what its address keys are written under and what keeps two tables on one page apart.');
    }

    public static function alreadyDeclaredByDefinition(string $declaration): self
    {
        return new self("A table drawn from its definition is also given a [{$declaration}], which the definition it was drawn from already declares.");
    }

    public static function uncaptioned(): self
    {
        return new self('A table declares no caption, which is what names the region its rows are read inside.');
    }

    public static function declaredBesideAHandedPage(string $declaration): self
    {
        return new self("A table is handed a page to open on and is also given a [{$declaration}], which the page it was handed already answers for.");
    }

    public static function deferredAndHandedAPage(): self
    {
        return new self('A table both waits until it is reached and is handed a page to open on, which is a page built for a table nobody may ever scroll to.');
    }

    public static function noExportColumns(): self
    {
        return new self('A table cannot be exported before its columns are declared.');
    }

    public static function tooManyRowsToExport(int $total, int $limit): self
    {
        return new self("An export of {$total} rows was attempted in a format capped at {$limit}.");
    }

    public static function pinnedWithoutWidth(string $key): self
    {
        return new self("The pinned column [{$key}] declares no width, which is what the column pinned behind it is placed from.");
    }

    public static function widthNotALength(string $key, string $value): self
    {
        return new self("The column [{$key}] declares the width [{$value}], which is not a length the columns pinned beside it can be placed from.");
    }

    public static function heightNotALength(string $value): self
    {
        return new self("A table declares the height [{$value}], which is not a length the pane its rows scroll inside can be capped at.");
    }

    public static function brokenStickyRun(Stick $edge): self
    {
        return new self("The columns pinned to the {$edge->value} of a table are not the ones drawn at that edge.");
    }
}
