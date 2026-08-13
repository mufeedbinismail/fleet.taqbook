<?php

namespace Tests\Unit\Foundation\Component\Select;

use App\Foundation\Component\Select\Support\DataAttributes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * What a row may carry alongside its name.
 *
 * The rules are about the round trip rather than about tidiness: whatever is written onto an option
 * element is read back off it later, and a name or a value that does not survive that journey comes
 * back as something nobody put there.
 */
class DataAttributesTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $given
     * @param  array<string, string>  $carries
     */
    #[DataProvider('agreed')]
    public function test_a_row_carries_what_both_sides_of_the_wire_agree_it_carries(array $given, array $carries): void
    {
        $this->assertSame($carries, DataAttributes::of($given));
    }

    /**
     * A row using one of these would be saying two different things under a single name, and
     * whichever was written second is the one anybody asking would get.
     */
    #[DataProvider('reserved')]
    public function test_a_name_the_option_already_stores_under_is_dropped(string $name): void
    {
        $this->assertSame([], DataAttributes::of([$name => 'CUST-0011']));
    }

    /**
     * An attribute holds a string and nothing else, and an object is the one value a query string
     * cannot state — kept, it would arrive on the far side as the word PHP stringifies it to and
     * read as a value somebody meant.
     */
    public function test_a_value_only_this_side_can_hold_is_dropped(): void
    {
        $this->assertSame([], DataAttributes::of(['branch' => new stdClass]));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, string>}>
     */
    public static function agreed(): iterable
    {
        foreach (self::contract()['cases'] as $guarantee => $case) {
            yield $guarantee => [$case['given'], $case['carries']];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function reserved(): iterable
    {
        foreach (self::contract()['reserved'] as $name) {
            yield $name => [$name];
        }
    }

    /**
     * @return array{reserved: list<string>, cases: array<string, array{given: array<string, mixed>, carries: array<string, string>}>}
     */
    private static function contract(): array
    {
        return json_decode(file_get_contents(test_path('contract/select-data-attributes.json')), true);
    }
}
