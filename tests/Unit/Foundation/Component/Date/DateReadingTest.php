<?php

namespace Tests\Unit\Foundation\Component\Date;

use App\Foundation\Component\Date\Constant\DateReading;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A day may already be written any of several ways by the time it gets here, and all of them have
 * to land on the same day: one that did not would be compared against days that had, and answer
 * differently for no reason anybody can see.
 */
class DateReadingTest extends TestCase
{
    private const IN_FORCE = 'd/m/Y';

    public function test_a_day_written_in_the_format_in_force_is_read_as_that_day(): void
    {
        $this->assertSame('2026-03-09', DateReading::day('09/03/2026', self::IN_FORCE));
    }

    #[DataProvider('fixedSpellings')]
    public function test_a_day_stored_in_a_fixed_spelling_is_read_whatever_format_is_in_force(string $text): void
    {
        $this->assertSame('2026-03-09', DateReading::day($text, self::IN_FORCE));
    }

    public function test_the_fixed_spellings_are_the_ones_the_contract_names(): void
    {
        $this->assertEqualsCanonicalizing(
            self::contract()['machineFormats'],
            DateReading::MACHINE_FORMATS,
        );
    }

    public function test_a_day_that_arrived_as_an_instant_is_taken_as_the_day_it_falls_on(): void
    {
        $this->assertSame(
            '2026-03-09',
            DateReading::day(new DateTimeImmutable('2026-03-09 15:05:00'), self::IN_FORCE),
        );
    }

    /**
     * *Fails if* a text nobody can read starts answering with today, which is a day somebody would
     * have to notice was wrong rather than one anything reports.
     */
    #[DataProvider('textsNamingNoDay')]
    public function test_a_text_texts_naming_no_day_is_read_as_no_day(?string $text): void
    {
        $this->assertNull(DateReading::day($text, self::IN_FORCE));
    }

    /**
     * *Fails if* an unreadable entry is kept as a hole: the days are marks rather than a list whose
     * length or position means anything, and a null among them is compared against every cell.
     */
    public function test_a_list_of_days_keeps_what_it_could_read_and_drops_the_rest(): void
    {
        $this->assertSame(
            ['2026-03-09', '2026-03-10'],
            DateReading::days(['09/03/2026', 'not a date', '2026-03-10', ''], self::IN_FORCE),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fixedSpellings(): iterable
    {
        // Written out rather than typed here, so a spelling added to the set needs nothing said
        // twice.
        $instant = DomainDateTime::create(2026, 3, 9, 15, 5, 0);

        foreach (self::contract()['machineFormats'] as $spelling) {
            yield "a day stored as `{$spelling}`" => [$instant->format($spelling)];
        }
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function textsNamingNoDay(): iterable
    {
        yield 'nothing at all' => [null];
        yield 'an empty field' => [''];
        yield 'spaces' => ['   '];
        yield 'text that is not a date' => ['not a date'];
        yield 'the all-zero date the legacy schema writes where there is no date' => ['0000-00-00'];
        yield 'a day past the end of the month it names' => ['31/02/2026'];
    }

    /**
     * @return array{machineFormats: list<string>}
     */
    private static function contract(): array
    {
        return json_decode(file_get_contents(test_path('contract/date-format.json')), true);
    }
}
