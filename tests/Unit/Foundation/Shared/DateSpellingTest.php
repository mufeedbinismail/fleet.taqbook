<?php

namespace Tests\Unit\Foundation\Shared;

use App\Foundation\Shared\Enum\DateSeparator;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * This side's half of the agreement about how a date is spelled, which is one rule rather than a
 * list: a text is a date written in a format only if writing that date back out under the format
 * reproduces the text.
 */
class DateSpellingTest extends TestCase
{
    /**
     * *Fails if* this side stops writing what the other is built to read.
     */
    #[DataProvider('writes')]
    public function test_a_date_is_written_the_way_the_browser_reads_one(string $format, string $text, string $as, string $means): void
    {
        $this->assertSame($text, DomainDateTime::fromFormat($as, $means)->format($format));
    }

    /**
     * *Fails if* this side stops reading what the other is built to write.
     */
    #[DataProvider('reads')]
    public function test_a_date_is_read_the_way_the_browser_writes_one(string $format, string $text, string $as, string $means): void
    {
        $this->assertSame($means, DomainDateTime::fromFormat($format, $text)->format($as));
    }

    /**
     * *Fails if* a date neither side should accept is accepted here — which is how the 31st of
     * February reaches a query as the 2nd of March.
     */
    #[DataProvider('refuses')]
    public function test_a_date_neither_side_accepts_is_refused(string $format, string $text): void
    {
        $this->assertNull(DomainDateTime::tryFromFormat($format, $text));
    }

    /**
     * *Fails if* this side ever starts translating a month or a day — which PHP's own formatting
     * cannot do, so it would take reaching for a translating formatter to break it.
     *
     * @param  list<string>  $expected
     */
    #[DataProvider('names')]
    public function test_a_month_or_a_day_is_named_the_way_both_sides_hold_it(string $token, array $expected): void
    {
        $written = match ($token) {
            'F', 'M' => array_map(
                fn (int $month) => DomainDateTime::create(2000, $month, 1)->format($token),
                range(1, 12),
            ),
            default => array_map(
                // A week indexed from Sunday, which the 7th of January 2001 was.
                fn (int $day) => DomainDateTime::create(2001, 1, 7)->addDays($day)->format($token),
                range(0, 6),
            ),
        };

        $this->assertSame($expected, $written);
    }

    /**
     * *Fails if* this side gains a separator the other was never told about: a format is split into
     * its halves along these, and an unknown one strands the punctuation on the wrong half.
     */
    public function test_a_date_is_joined_only_by_the_separators_the_contract_names(): void
    {
        $this->assertEqualsCanonicalizing(
            self::contract()['separators'],
            array_values(DateSeparator::labels()),
        );
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function names(): iterable
    {
        $held = self::contract()['names'];

        yield 'months written out' => ['F', $held['months']];
        yield 'months abbreviated' => ['M', $held['monthsShort']];
        yield 'days written out' => ['l', $held['days']];
        yield 'days abbreviated' => ['D', $held['daysShort']];
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function writes(): iterable
    {
        foreach (self::contract()['writes'] as $guarantee => $case) {
            yield $guarantee => [$case['format'], $case['text'], $case['as'], $case['means']];
        }
    }

    /**
     * The same cases read back, since stating them twice would let the two halves drift.
     *
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function reads(): iterable
    {
        return self::writes();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function refuses(): iterable
    {
        foreach (self::contract()['refuses'] as $guarantee => $case) {
            yield $guarantee => [$case['format'], $case['text']];
        }
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private static function contract(): array
    {
        return json_decode(file_get_contents(test_path('contract/date-format.json')), true);
    }
}
