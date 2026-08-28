<?php

namespace Tests\Unit\Foundation\Shared;

use App\Foundation\Shared\Enum\DateFormat;
use App\Foundation\Shared\Enum\DateSystem;
use App\Foundation\Shared\Enum\WeekDay;
use App\Foundation\Shared\Setting\UserSetting;
use Tests\TestCase;

/**
 * Which day a week begins on for an account, whether or not anybody there ever said.
 */
class WeekStartTest extends TestCase
{
    public function test_an_account_that_never_chose_begins_its_week_where_its_date_order_implies(): void
    {
        // Every order there is, since a rule checked over part of a set is one nobody can tell has
        // stopped holding for the rest.
        $implied = [
            DateFormat::MMDDYYYY->name => WeekDay::Sunday,
            DateFormat::MmmDYYYY->name => WeekDay::Sunday,
            DateFormat::DDMMYYYY->name => WeekDay::Monday,
            DateFormat::YYYYMMDD->name => WeekDay::Monday,
            DateFormat::DMmmYYYY->name => WeekDay::Monday,
            DateFormat::YYYYMmmD->name => WeekDay::Monday,
        ];

        foreach (DateFormat::cases() as $order) {
            $this->assertSame($implied[$order->name], $this->account($order)->weekStart(), $order->name);
        }
    }

    public function test_a_calendar_whose_week_begins_on_a_saturday_answers_ahead_of_the_date_order(): void
    {
        foreach (DateSystem::cases() as $system) {
            config(['date.calendar_system_id' => $system->value]);

            $expected = $system === DateSystem::Traditional ? WeekDay::Sunday : WeekDay::Saturday;

            $this->assertSame(
                $expected,
                $this->account(DateFormat::MMDDYYYY)->weekStart(),
                $system->name,
            );
        }
    }

    /**
     * Sunday is nought and a question nobody answered is null, so a chosen Sunday has to survive
     * being read beside a silence.
     */
    public function test_a_day_somebody_chose_stands_whatever_would_have_been_implied(): void
    {
        $this->assertSame(WeekDay::Monday, $this->account(DateFormat::DDMMYYYY)->weekStart());

        $this->assertSame(
            WeekDay::Saturday,
            $this->account(DateFormat::DDMMYYYY, WeekDay::Saturday)->weekStart(),
        );

        $this->assertSame(
            WeekDay::Sunday,
            $this->account(DateFormat::DDMMYYYY, WeekDay::Sunday)->weekStart(),
        );
    }

    private function account(DateFormat $order, ?WeekDay $chosen = null): UserSetting
    {
        return new UserSetting([
            'date_format' => $order->value,
            'week_start' => $chosen?->value,
        ]);
    }
}
