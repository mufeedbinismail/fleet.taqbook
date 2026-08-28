<?php

namespace Tests\Feature\Foundation\Component\Date;

use App\Foundation\Shared\Enum\DateFormat;
use App\Foundation\Shared\Enum\DateSeparator;
use App\Foundation\Shared\Setting\UserSetting;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * What a period posts before a line of script has run, and how the pair reads to somebody who
 * cannot see it.
 *
 * Which days either end will accept is not asked here: that is settled while somebody is picking,
 * and a case reading it off the markup passes whether or not anything acts on it.
 */
class DateRangeFieldTest extends TestCase
{
    /**
     * What an end is called is a fact about whatever it is posted to — two columns, two request
     * keys — rather than a shape worked out from a name for the pair.
     */
    public function test_each_end_posts_its_own_day_under_the_name_it_was_given(): void
    {
        $inputs = $this->inputs($this->rendered(
            '<x-ui.date-range from-name="check_in" to-name="check_out" :from="$from" :to="$to" />',
            ['from' => '2026-03-09', 'to' => '2026-03-14'],
        ));

        $this->assertSame('09/03/2026', $inputs['check_in']['value']);
        $this->assertSame('14/03/2026', $inputs['check_out']['value']);
    }

    /**
     * *Fails if* a period stops being expressible with one end open, which is most of the periods
     * anybody asks for and the reason a single box will not do.
     */
    public function test_a_period_may_be_given_one_end_and_left_open_at_the_other(): void
    {
        $inputs = $this->inputs($this->rendered(
            '<x-ui.date-range from-name="check_in" to-name="check_out" :from="$from" />',
            ['from' => '2026-03-09'],
        ));

        $this->assertSame('09/03/2026', $inputs['check_in']['value']);
        $this->assertSame('', $inputs['check_out']['value']);
    }

    /**
     * *Fails if* something stated on the pair stops arriving at an end: what is dropped is
     * replaced by a plausible answer rather than an empty one, so nothing reports it.
     */
    public function test_what_the_period_was_told_reaches_both_of_its_ends(): void
    {
        $html = $this->rendered(
            '<x-ui.date-range from-name="check_in" to-name="check_out"
                min="2026-01-01" max="2026-12-31" first-day="6" panel-parent=".scroller" />',
        );

        foreach ($this->inputs($html) as $name => $input) {
            $this->assertSame('2026-01-01', $input['config']['min'] ?? null, "{$name} lost the floor");
            $this->assertSame('2026-12-31', $input['config']['max'] ?? null, "{$name} lost the ceiling");
            $this->assertSame('6', $input['config']['firstDay'] ?? null, "{$name} lost the week start");
            $this->assertSame('.scroller', $input['config']['panelParent'] ?? null, "{$name} lost the panel's parent");
        }
    }

    /**
     * *Fails if* the naming comes apart: read aloud, the pair becomes two unrelated dates and which
     * of them is the start is knowable only by looking.
     */
    public function test_the_period_is_named_once_and_each_end_is_named_by_a_label_that_reaches_it(): void
    {
        $html = $this->rendered(
            '<x-ui.date-range from-name="check_in" to-name="check_out"
                legend="Stay" from-label="Check in" to-label="Check out" />',
        );

        $this->assertMatchesRegularExpression('/<legend[^>]*>\s*Stay\s*</', $html);

        preg_match_all('/<label[^>]*\bfor="([^"]*)"[^>]*>(.*?)<\/label>/s', $html, $labels);

        $named = array_combine($labels[1], array_map('trim', $labels[2]));

        $this->assertSame(['Check in', 'Check out'], array_values($named));

        // An id that reaches nothing reads aloud as an unlabelled box, which is the state the
        // label was added to avoid.
        foreach (array_keys($named) as $target) {
            $this->assertStringContainsString('id="'.$target.'"', $html, "{$target} names no element");
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rendered(string $template, array $data = []): string
    {
        $this->app->instance(UserSetting::class, new UserSetting([
            'date_format' => DateFormat::DDMMYYYY->value,
            'date_sep' => DateSeparator::SLASH->value,
        ]));

        return Blade::render($template, $data);
    }

    /**
     * Every date field in the markup, keyed by what it posts under.
     *
     * @return array<string, array{value: string, config: array<string, mixed>}>
     */
    private function inputs(string $html): array
    {
        preg_match_all('/<input[^>]*\bx-date[^>]*>/s', $html, $found);

        $fields = [];

        foreach ($found[0] as $tag) {
            preg_match('/name="([^"]*)"/', $tag, $name);
            preg_match('/value="([^"]*)"/', $tag, $value);
            preg_match('/data-date="([^"]*)"/', $tag, $config);

            $fields[$name[1] ?? ''] = [
                'value' => htmlspecialchars_decode($value[1] ?? '', ENT_QUOTES),
                'config' => json_decode(htmlspecialchars_decode($config[1] ?? '{}', ENT_QUOTES), true) ?? [],
            ];
        }

        return $fields;
    }
}
