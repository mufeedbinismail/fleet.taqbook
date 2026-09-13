<?php

namespace Tests\Feature\Foundation\Component\Select;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * What a select posts before a line of script has run.
 */
class SelectMarkupTest extends TestCase
{
    /**
     * A clerk opens an edit form and saves without touching the customer. The browser posts the
     * option marked selected — or, given no such mark and no valueless row, the first row it has.
     */
    public function test_an_edit_form_posts_the_record_it_was_opened_on(): void
    {
        $html = $this->render(['selected' => 2]);

        $this->assertMatchesRegularExpression('/value="2"\s+selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="1"\s+selected/', $html);
    }

    /**
     * A new form nobody touched. Without a valueless row first, the browser posts the first
     * customer as though somebody had picked them.
     */
    public function test_a_new_form_posts_no_customer_until_one_is_picked(): void
    {
        $this->assertStringContainsString(
            '<option value=""></option>',
            $this->render(['placeholder' => 'Pick one']),
        );
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

        return Blade::render("<x-ui.select {$attributes} />", $props);
    }
}
