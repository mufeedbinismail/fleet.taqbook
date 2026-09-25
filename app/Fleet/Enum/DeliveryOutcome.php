<?php

namespace App\Fleet\Enum;

use App\Foundation\Framework\Concern\HasLabelConcern;
use App\Foundation\Framework\Contract\HasLabelContract;

enum DeliveryOutcome: string implements HasLabelContract
{
    use HasLabelConcern;

    case Reached = 'reached';
    case Unreachable = 'unreachable';
    case Refused = 'refused';
    case WrongAnswer = 'wrong_answer';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Reached->value => __('fleet.deployment.delivery_outcome.reached'),
            self::Unreachable->value => __('fleet.deployment.delivery_outcome.unreachable'),
            self::Refused->value => __('fleet.deployment.delivery_outcome.refused'),
            self::WrongAnswer->value => __('fleet.deployment.delivery_outcome.wrong_answer'),
        ];
    }
}
