<?php

namespace Tests\Feature\Foundation\Component\Select;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * What a select posts before a line of script has run.
 *
 * Everything here reads the markup rather than the control drawn from it: a screen whose scripts
 * never arrive still has to submit the value it was opened with, and a form that posts a row nobody
 * chose is wrong however well the control behaves once it is running.
 */
class SelectComponentTest extends TestCase
{
    public function test_an_edit_form_posts_the_value_it_was_opened_with(): void
    {
        $html = $this->render(['selected' => 2]);

        $this->assertMatchesRegularExpression('/value="2"\s+selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="1"\s+selected/', $html);
    }

    /**
     * A single select shows its first row as though somebody had picked it unless a valueless row
     * comes first, so a form nobody touched posts a customer nobody chose.
     */
    public function test_a_placeholder_makes_choosing_nothing_possible(): void
    {
        $this->assertStringContainsString(
            '<option value=""></option>',
            $this->render(['placeholder' => 'Pick one']),
        );

        $this->assertStringNotContainsString('<option value=""></option>', $this->render([]));
    }

    /**
     * The row that cannot be chosen has to say so in the markup too, or a form submitted without
     * scripts accepts what the control would have refused.
     */
    public function test_a_row_nobody_may_choose_is_refused_by_the_markup(): void
    {
        $html = $this->render([
            'options' => [
                ['value' => 1, 'label' => 'ACME Corp'],
                ['value' => 2, 'label' => 'Beta Trading', 'disabled' => true],
            ],
        ]);

        $this->assertMatchesRegularExpression('/value="2"\s+disabled/', $html);
    }

    public function test_a_screen_supplies_its_rows_in_the_shape_it_already_holds_them(): void
    {
        $html = $this->render([
            'options' => [
                ['stock_id' => 'A-1', 'description' => 'Widget', 'note' => 'In stock'],
            ],
            'valueField' => 'stock_id',
            'labelField' => 'description',
            'descriptionField' => 'note',
        ]);

        $this->assertStringContainsString('value="A-1"', $html);
        $this->assertStringContainsString('>Widget</option>', $html);
        $this->assertStringContainsString('data-description="In stock"', $html);
    }

    /**
     * A row's extra columns are what the screen reading the chosen option works from — a unit to
     * price against, a currency to convert into. Left out of the markup, a screen whose scripts have
     * not arrived has to go and fetch each one back by the value it is already holding.
     */
    public function test_a_row_carries_its_own_extra_columns_into_the_markup(): void
    {
        $html = $this->render([
            'options' => [
                ['value' => 1, 'label' => 'ACME Corp', 'data' => ['currency' => 'AED', 'credit_days' => 30]],
            ],
        ]);

        $this->assertStringContainsString('data-currency="AED"', $html);
        $this->assertStringContainsString('data-credit_days="30"', $html);
    }

    /**
     * A name the element cannot answer back under is worse than one it never carried: written as
     * `data-credit-days` it is read back as `creditDays`, and a row that went through the control
     * once comes out saying something nobody wrote. The row and its other columns are asserted
     * alongside, so a run that dropped the whole option cannot pass as one that dropped only the
     * bad key.
     */
    public function test_a_name_the_element_could_not_answer_back_under_is_not_written(): void
    {
        $html = $this->render([
            'options' => [
                ['value' => 1, 'label' => 'ACME Corp', 'data' => ['credit-days' => 30, 'currency' => 'AED']],
            ],
        ]);

        $this->assertMatchesRegularExpression('/value="1"[^>]*>ACME Corp</', $html);
        $this->assertStringContainsString('data-currency="AED"', $html);
        $this->assertStringNotContainsString('credit-days', $html);
        $this->assertStringNotContainsString('creditDays', $html);
    }

    /**
     * A fetching select is rendered holding only what was chosen, so the label is right on first
     * paint and the term the user types is the first thing the server is ever asked about.
     */
    public function test_a_fetching_select_carries_the_chosen_row_and_where_to_ask_for_the_rest(): void
    {
        $html = $this->render([
            'url' => '/customers/options',
            'options' => [['value' => 42, 'label' => 'ACME Corp']],
            'selected' => 42,
        ]);

        $this->assertMatchesRegularExpression('/value="42"\s+selected/', $html);
        $this->assertStringContainsString('>ACME Corp</option>', $html);
        $this->assertStringEndsWith('/customers/options', $this->config($html)['url']);
    }

    /**
     * A screen narrowing a list by a box of its own hands over the box rather than its answer, and
     * the control reads it from then on. Lost on the way to the browser, the narrowing simply never
     * happens — the list stays wide and says nothing about why.
     */
    public function test_a_select_is_told_which_controls_answer_for_its_filters(): void
    {
        $html = $this->render([
            'url' => '/customers/options',
            'paramSources' => ['show_inactive' => '[name="show_inactive"]'],
        ]);

        $this->assertSame(
            ['show_inactive' => '[name="show_inactive"]'],
            $this->config($html)['paramSources'],
        );
    }

    /**
     * The configuration as the browser will actually receive it, rather than as it was written —
     * a control told where to fetch in a form the browser cannot read is told nothing.
     *
     * @return array<string, mixed>
     */
    private function config(string $html): array
    {
        preg_match('/data-select="([^"]*)"/', $html, $found);

        return json_decode(html_entity_decode($found[1], ENT_QUOTES), true);
    }

    private function render(array $props): string
    {
        $props = array_merge([
            'name' => 'customer_id',
            'options' => [
                ['value' => 1, 'label' => 'ACME Corp'],
                ['value' => 2, 'label' => 'Beta Trading'],
            ],
        ], $props);

        $attributes = collect($props)
            ->map(fn ($value, $key) => is_string($value)
                ? $key.'="'.$value.'"'
                : ':'.$key.'="$'.$key.'"')
            ->implode(' ');

        return Blade::render("<x-select {$attributes} />", $props);
    }
}
