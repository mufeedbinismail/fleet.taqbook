<?php

namespace Tests\Unit\Navigation;

use App\Foundation\Navigation\ValueObject\TranslatedLabel;
use Tests\TestCase;

class TranslatedLabelTest extends TestCase
{
    /**
     * The property that lets one declaration be shared by requests that disagree about language:
     * a label built once must answer in whatever locale is current when it is read.
     */
    public function test_a_label_read_twice_answers_in_the_locale_of_each_read(): void
    {
        $label = TranslatedLabel::of('Sales');

        $this->assertSame('Sales', $label->text());

        $this->translate('Sales', 'Ventes');

        $this->assertSame('Ventes', $label->text());
    }

    /**
     * A message with no entry in the catalog has to survive the round trip unchanged, or every
     * label declared before the catalog exists would render as its own key.
     */
    public function test_an_untranslated_message_reads_as_itself(): void
    {
        $this->assertSame('Sales', TranslatedLabel::of('Sales')->text());
    }

    public function test_a_plain_label_marks_no_accelerator(): void
    {
        $this->assertNull(TranslatedLabel::of('Sales')->accessKey());
    }

    private function translate(string $message, string $line): void
    {
        $this->app['translator']->addLines(['*.'.$message => $line], $this->app->getLocale());
    }
}
