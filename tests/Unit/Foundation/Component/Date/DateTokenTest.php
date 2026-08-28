<?php

namespace Tests\Unit\Foundation\Component\Date;

use App\Foundation\Component\Date\Constant\DateToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Which of the two halves — a day, a time of day — a format spells at all, which is the question
 * behind whether a day filled in is a day that goes anywhere.
 */
class DateTokenTest extends TestCase
{
    /**
     * *Fails if* this side gains a token the other was never given, or loses one it still holds.
     * The set is the agreement; the order is not.
     */
    public function test_the_tokens_of_each_half_are_the_ones_the_contract_names(): void
    {
        $held = self::contract()['tokens'];

        $this->assertEqualsCanonicalizing($held['date'], DateToken::DATE);
        $this->assertEqualsCanonicalizing($held['time'], DateToken::TIME);
    }

    /**
     * *Fails if* a token stops spelling the half it is held to on both sides of the wire.
     */
    #[DataProvider('tokens')]
    public function test_a_token_spells_the_half_both_sides_hold_it_to(string $token, bool $carriesDate, bool $carriesTime): void
    {
        $this->assertSame($carriesDate, DateToken::carriesDate($token));
        $this->assertSame($carriesTime, DateToken::carriesTime($token));
    }

    /**
     * *Fails if* the two sides stop agreeing about what a whole format asks for — above all about
     * escaped letters, which are text rather than tokens.
     */
    #[DataProvider('halves')]
    public function test_a_format_asks_for_the_halves_both_sides_hold_it_to(string $format, bool $carriesDate, bool $carriesTime): void
    {
        $this->assertSame($carriesDate, DateToken::carriesDate($format));
        $this->assertSame($carriesTime, DateToken::carriesTime($format));
    }

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function tokens(): iterable
    {
        $held = self::contract()['tokens'];

        foreach ($held['date'] as $token) {
            yield "`{$token}` spells a date" => [$token, true, false];
        }

        foreach ($held['time'] as $token) {
            yield "`{$token}` spells a time" => [$token, false, true];
        }
    }

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function halves(): iterable
    {
        foreach (self::contract()['halves'] as $guarantee => $case) {
            yield $guarantee => [$case['format'], $case['carriesDate'], $case['carriesTime']];
        }
    }

    /**
     * @return array{tokens: array{date: list<string>, time: list<string>}, halves: array<string, array{format: string, carriesDate: bool, carriesTime: bool}>}
     */
    private static function contract(): array
    {
        return json_decode(file_get_contents(test_path('contract/date-format.json')), true);
    }
}
