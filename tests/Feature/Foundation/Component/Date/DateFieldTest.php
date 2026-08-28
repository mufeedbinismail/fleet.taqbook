<?php

namespace Tests\Feature\Foundation\Component\Date;

use App\Foundation\Component\Date\Source\DateSource;
use App\Foundation\Framework\Facade\ClientData;
use App\Foundation\Framework\Registry\ClientDataRegistry;
use App\Foundation\Shared\Enum\DateFormat;
use App\Foundation\Shared\Enum\DateSeparator;
use App\Foundation\Shared\Setting\UserSetting;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * What a date field posts before a line of script has run, and what a page hands over for the
 * scripts to work from once they do.
 *
 * Nothing here reads the configuration the markup carries: what a field is told is not a promise to
 * anybody, and a case asserting it passes just as happily when nothing acts on it.
 */
class DateFieldTest extends TestCase
{
    /**
     * *Fails if* the day or the name stops reaching the element: the field draws correctly and
     * posts nothing, which is reported only as a date nobody filled in.
     */
    public function test_a_form_posts_the_day_it_was_opened_with_under_the_name_it_was_given(): void
    {
        $html = $this->rendered(DateFormat::DDMMYYYY, DateSeparator::SLASH);

        $this->assertStringContainsString('name="due_on"', $html);
        $this->assertStringContainsString('value="09/03/2026"', $html);
    }

    /**
     * *Fails if* the preference stops being read: a date is then read off by a month whenever the
     * day is twelve or less, and nothing on the page corrects it.
     */
    public function test_a_day_is_spelled_the_way_the_person_looking_at_it_writes_dates(): void
    {
        $this->assertStringContainsString(
            'value="09/03/2026"',
            $this->rendered(DateFormat::DDMMYYYY, DateSeparator::SLASH),
        );

        $this->assertStringContainsString(
            'value="03/09/2026"',
            $this->rendered(DateFormat::MMDDYYYY, DateSeparator::SLASH),
        );

        $this->assertStringContainsString(
            'value="09.03.2026"',
            $this->rendered(DateFormat::DDMMYYYY, DateSeparator::DOT),
        );
    }

    /**
     * *Fails if* a field asked for a time stops carrying one: what is posted is then a day where a
     * moment was meant, and it reads as a midnight somebody chose.
     */
    public function test_a_field_asked_for_a_time_posts_the_time_as_well_as_the_day(): void
    {
        $this->assertStringContainsString(
            'value="09/03/2026 03:05 PM"',
            $this->rendered(
                DateFormat::DDMMYYYY,
                DateSeparator::SLASH,
                '<x-ui.date name="due_on" time :value="$value" />',
            ),
        );
    }

    /**
     * *Fails if* a field that posts nowhere starts posting: an empty key arrives beside the real
     * ones and is written wherever a blank date is accepted.
     */
    public function test_a_field_given_no_name_posts_nothing(): void
    {
        $this->assertStringNotContainsString(
            'name=',
            $this->rendered(DateFormat::DDMMYYYY, DateSeparator::SLASH, '<x-ui.date :value="$value" />'),
        );
    }

    /**
     * `x-date` on a plain input is the whole of what a field takes, so a page carrying one may have
     * rendered no component at all.
     *
     * *Fails if* the defaults stop reaching such a page: it goes on working while showing everybody
     * the fixed spelling instead of their own, with nothing on screen reporting it.
     */
    public function test_a_page_that_rendered_no_date_field_still_hands_over_the_preference(): void
    {
        $this->preferring(DateFormat::DDMMYYYY, DateSeparator::SLASH);

        $staged = $this->handedOver()[DateSource::NAMESPACE] ?? [];

        $this->assertSame('d/m/Y', $staged['format'] ?? null);
        $this->assertSame(1, $staged['firstDay'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function handedOver(): array
    {
        $printed = ClientData::render();

        $this->assertMatchesRegularExpression('/^JSON\.parse\(\'.*\'\)$/s', $printed);

        preg_match('/^JSON\.parse\(\'(.*)\'\)$/s', $printed, $found);

        return json_decode(json_decode('"'.$found[1].'"'), true);
    }

    private function preferring(DateFormat $format, DateSeparator $separator): void
    {
        $this->app->instance(UserSetting::class, new UserSetting([
            'date_format' => $format->value,
            'date_sep' => $separator->value,
        ]));

        // Dropped rather than reconfigured, so the next one is built after the preference was
        // stated rather than before it.
        $this->app->forgetInstance(ClientDataRegistry::class);
    }

    /**
     * @param  string|null  $template  the markup under test, where a case needs other than the plain field
     */
    private function rendered(DateFormat $format, DateSeparator $separator, ?string $template = null): string
    {
        $this->preferring($format, $separator);

        return Blade::render(
            $template ?? '<x-ui.date name="due_on" :value="$value" />',
            ['value' => '2026-03-09 15:05:00'],
        );
    }
}
