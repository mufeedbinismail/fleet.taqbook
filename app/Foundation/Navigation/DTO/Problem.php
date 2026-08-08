<?php

namespace App\Foundation\Navigation\DTO;

/**
 * One thing wrong with the sitemap.
 *
 * Reported as a value rather than thrown, so a typo in one domain costs one subtree instead of
 * every page in the application.
 */
final class Problem
{
    public const DUPLICATE_KEY = 'duplicate-key';

    public const MISSING_PARENT = 'missing-parent';

    public const MISSING_SECTION = 'missing-section';

    public const SECTION_MISPLACED = 'section-misplaced';

    public const HIDDEN_PARENT = 'hidden-parent';

    public const MISSING_TARGET = 'missing-target';

    public const UNRESOLVABLE_TARGET = 'unresolvable-target';

    public const UNKNOWN_PERMISSION = 'unknown-permission';

    public const UNKNOWN_CONDITION = 'unknown-condition';

    public const AMBIGUOUS_AREA_SORT = 'ambiguous-area-sort';

    public const DUPLICATE_TARGET = 'duplicate-target';

    public const CYCLE = 'cycle';

    public const FROZEN = 'frozen';

    /**
     * @param  bool  $fatal  whether this makes the subject unusable, as opposed to merely worth
     *                       reporting
     */
    public function __construct(
        public readonly string $type,
        public readonly string $subject,
        public readonly string $detail,
        public readonly bool $fatal = true,
    ) {}

    /**
     * Worded once here because a collision is worth refusing outright as well as reporting, and
     * the two answers should not drift apart.
     */
    public static function duplicateKey(string $key): self
    {
        return new self(
            self::DUPLICATE_KEY,
            $key,
            'Declared more than once. Keys are global — namespace them to the domain that owns '
            .'the entry.',
        );
    }
}
