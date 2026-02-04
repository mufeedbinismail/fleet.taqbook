<?php

namespace App\Foundation\Contract\Enum;

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
     *
     * @return string
     */
    public function label(): string;

    /**
     * Labels keyed by enum value.
     *
     * @return array<mixed, string>
     */
    public static function labels(): array;
}