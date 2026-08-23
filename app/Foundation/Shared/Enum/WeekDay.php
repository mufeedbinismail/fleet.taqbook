<?php

namespace App\Foundation\Shared\Enum;

use App\Foundation\Framework\Concern\Enum\HasLabelConcern;
use App\Foundation\Framework\Contract\Enum\HasLabelContract;

/**
 * Sunday at nought, matching where JavaScript's own week begins, so the value crosses to the
 * browser untranslated.
 */
enum WeekDay: int implements HasLabelContract
{
    use HasLabelConcern;

    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    /**
     * A day somebody named, or null where they named none.
     *
     * Emptiness is answered before the cast because `(int) null` and `(int) ''` are both nought,
     * and nought is Sunday — cast first and every silence reads as a chosen Sunday, an answer
     * nothing downstream can tell was never given.
     */
    public static function fromChoice(mixed $said): ?self
    {
        return $said === null || $said === '' ? null : self::tryFrom((int) $said);
    }

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            self::Sunday->value => 'Sunday',
            self::Monday->value => 'Monday',
            self::Tuesday->value => 'Tuesday',
            self::Wednesday->value => 'Wednesday',
            self::Thursday->value => 'Thursday',
            self::Friday->value => 'Friday',
            self::Saturday->value => 'Saturday',
        ];
    }
}
