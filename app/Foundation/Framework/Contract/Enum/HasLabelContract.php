<?php

namespace App\Foundation\Framework\Contract\Enum;

/**
 * This interface provides a common contract for enums to have a label.
 *
 * @example
 * ```php
 * // old array
 * $dateseps = ['/', '.', '-', ' '];
 *
 * // new enum
 * enum DateSeparator: int implements HasLabel
 * {
 *     case SLASH = 0;
 *     case DOT = 1;
 *     case DASH = 2;
 *     case SPACE = 3;
 *
 *     public function label(): string
 *     {
 *         return self::labels()[$this->value];
 *     }
 *
 *     public static function labels(): array
 *     {
 *         return [
 *             self::SLASH->value => '/',
 *             self::DOT->value => '.',
 *             self::DASH->value => '-',
 *             self::SPACE->value => ' ',
 *         ];
 *     }
 * }
 * ```
 */
interface HasLabelContract
{
    /**
     * Label for the enum value.
     */
    public function label(): string;

    /**
     * Labels keyed by enum value.
     *
     * @return array<mixed, string>
     */
    public static function labels(): array;

    /**
     * The set as a control offers it.
     *
     * @return list<array{value: int|string, label: string}>
     */
    public static function choices(): array;
}
